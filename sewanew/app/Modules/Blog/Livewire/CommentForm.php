<?php

namespace App\Modules\Blog\Livewire;

use Livewire\Component;
use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Models\Comment;
use Illuminate\Support\Facades\Auth;

class CommentForm extends Component
{
    public string $postId;
    public string $authorName = '';
    public string $authorEmail = '';
    public string $content = '';
    public ?string $parentId = null;

    protected $rules = [
        'authorName' => 'required|string|max:255',
        'authorEmail' => 'required|email|max:255',
        'content' => 'required|string|min:10|max:2000',
    ];

    public function mount(string $postId): void
    {
        $this->postId = $postId;

        if (Auth::check()) {
            $this->authorName = Auth::user()->name;
            $this->authorEmail = Auth::user()->email;
        }
    }

    public function submit(): void
    {
        $this->validate();

        Comment::create([
            'post_id' => $this->postId,
            'user_id' => Auth::id(),
            'author_name' => $this->authorName,
            'author_email' => $this->authorEmail,
            'content' => $this->content,
            'is_approved' => false,
            'parent_id' => $this->parentId,
        ]);

        $this->reset(['authorName', 'authorEmail', 'content', 'parentId']);

        $this->dispatch('comment-submitted');
    }

    public function replyTo(string $commentId, string $authorName): void
    {
        $this->parentId = $commentId;
        $this->authorName = Auth::check() ? Auth::user()->name : '';
        $this->dispatch('scroll-to-form');
    }

    public function cancelReply(): void
    {
        $this->reset(['parentId']);
    }

    public function render()
    {
        return view('blog::livewire.comment-form');
    }
}
