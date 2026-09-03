<?php

namespace App\Modules\Cms\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Modules\Cms\Models\Media;
use App\Modules\Cms\Models\MediaCollection;
use Illuminate\Support\Facades\Storage;

class MediaManager extends Component
{
    use WithFileUploads;

    public $files = [];
    public ?MediaCollection $collection = null;
    public string $search = '';
    public string $filter = 'all'; // all, image, video, document, audio
    public int $perPage = 24;
    public bool $showUploadModal = false;
    public ?Media $selectedMedia = null;
    public string $altText = '';

    protected $listeners = ['fileUploaded' => 'handleFileUpload'];

    public function mount(?int $collectionId = null): void
    {
        if ($collectionId) {
            $this->collection = MediaCollection::findOrFail($collectionId);
        }
    }

    public function getMediaProperty()
    {
        $query = Media::query()
            ->when($this->collection, fn($q) => 
                $q->where('collection_id', $this->collection->id)
            )
            ->when($this->search, fn($q) => 
                $q->where('name', 'like', "%{$this->search}%")
                 ->orWhere('alt_text', 'like', "%{$this->search}%")
            )
            ->when($this->filter !== 'all', fn($q) => 
                $q->where('type', $this->filter)
            )
            ->orderBy('created_at', 'desc');

        return $query->paginate($this->perPage);
    }

    public function updatedFiles(): void
    {
        foreach ($this->files as $file) {
            $this->uploadFile($file);
        }
        
        $this->files = [];
        $this->showUploadModal = false;
        session()->flash('success', 'Files uploaded successfully.');
    }

    protected function uploadFile($file): Media
    {
        $path = $file->store('media/' . date('Y/m'), 'public');
        
        $mimeType = $file->getMimeType();
        $type = explode('/', $mimeType)[0];
        
        return Media::create([
            'collection_id' => $this->collection?->id,
            'name' => $file->getClientOriginalName(),
            'file_name' => basename($path),
            'path' => $path,
            'mime_type' => $mimeType,
            'type' => $type,
            'size' => $file->getSize(),
            'alt_text' => $this->altText ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'uploaded_by' => auth()->id(),
        ]);
    }

    public function selectMedia(Media $media): void
    {
        $this->selectedMedia = $media;
        $this->altText = $media->alt_text ?? '';
    }

    public function updateAltText(): void
    {
        if (!$this->selectedMedia) {
            return;
        }

        $this->selectedMedia->update([
            'alt_text' => $this->altText,
        ]);

        session()->flash('success', 'Alt text updated successfully.');
        $this->selectedMedia = null;
    }

    public function deleteMedia(Media $media): void
    {
        if (auth()->user()->cannot('delete', $media)) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        Storage::disk('public')->delete($media->path);
        $media->delete();
        
        session()->flash('success', 'Media deleted successfully.');
        
        if ($this->selectedMedia?->id === $media->id) {
            $this->selectedMedia = null;
        }
    }

    public function createCollection(string $name): void
    {
        MediaCollection::create([
            'name' => $name,
            'slug' => \Str::slug($name),
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Collection created successfully.');
    }

    public function render()
    {
        return view('cms::livewire.media-manager', [
            'media' => $this->media,
        ]);
    }
}
