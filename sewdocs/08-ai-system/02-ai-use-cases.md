# 02 — AI Use Cases

**Every AI application on the platform — what it does, where it runs, its human gate, its fallback, and its policy boundary. AI assists people and scales content; it never publishes unsupervised or touches clients directly.**

---

## 1. Visitor-language identification & serving (flagship)
**Where:** I18n detection pipeline ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) §3)
- Deterministic detection (path/cookie/Accept-Language/geo) — not AI.
- **AI's role:** message-language detection for inbound leads (Korean/Japanese/Turkish/Arabic messages flagged so the ack email and consultant briefing match), and locale-copy polishing during review (suggestions only).
- Human gate: reviewer approves locale copy; lead-language detection is advisory (consultant sees it, decides how to respond — reply-any-language note included).
- Fallback: detection off → EN ack + "write in any language" line; consultants use their languages.

## 2. Content translation pipeline (scale play)
**Where:** I18n module queue ([../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) §4)
- Entity published in EN → translation job per enabled locale → machine draft (`status=machine`) → side-by-side review → approved (`status=human`) → locale publishable.
- Register rules per locale applied at review ([../06-content-seo/04-multilingual-content.md](../06-content-seo/04-multilingual-content.md) §3); terminology glossary standardized (FRRO, PAN, OCI, corporate housing terms).
- Auto-publish allowlist only after quality stabilizes (nav labels, simple UI strings).
- Fallback: provider down → queue parks, EN content keeps serving, hreflang stays truthful. **Nothing blocks.**

## 3. Lead enrichment (consultant pre-read)
**Where:** Leads module ([../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md) §4.2)
- From message text only (no PII sent beyond company name/city/service): suggested segment, message language, one-line summary, priority hint.
- Human gate: suggestions render in an "AI assist (draft)" panel; the assigned consultant decides. Nothing auto-assigned, auto-scored into pipeline stages.
- Fallback: breaker open → panel shows "enrichment paused"; the lead flow is unaffected.

## 4. Content assistance (editor tools, not ghostwriters)
**Where:** Blog/City editors ([../04-modules/07-blog-news.md](../04-modules/07-blog-news.md) §4)
- Brief compiler: harvest (zero-result searches, PAA-style questions, community phrasing) → clustered outline suggestion for the author.
- Draft suggestions: intro variants, section summaries, meta-title/description candidates (all just suggestions in the editor sidebar).
- **Policy:** a named human author owns every published piece; AI-assisted drafts are marked internally and reviewed like any draft ([../06-content-seo/01-content-strategy.md](../06-content-seo/01-content-strategy.md) §5 — "No AI-raw drafts").
- Fallback: assist buttons hidden when AI disabled; authors proceed normally.

## 5. FAQ harvesting (the flywheel)
**Where:** Content backlog tool ([../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md) §5)
- Zero-result searches + AI-probe questions clustered → candidate FAQ lists per page → editor curation queue (approve/edit/reject) → published as real FAQ blocks with schema.
- Human gate: 100% editor-curated before publish.
- Fallback: manual backlog grooming (the pre-AI process still documented and valid).

## 6. Consultant chat assist (portal, Phase 1.5)
**Where:** Portal threads ([../04-modules/04-client-portal.md](../04-modules/04-client-portal.md) §4.4)
- Reply-suggestion chip in the consultant's reply box (drafts a polite, context-aware response from thread history — names masked in outbound context).
- Consultant edits and sends; AI never messages clients directly at launch (policy line — a future "Ask Sewa" assistant is Phase-2 and will be clearly labeled, [../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md) §6).
- Fallback: chip absent when AI off; typing replies unaffected.

## 7. Internal summaries
**Where:** Ops digest, thread summaries, application digests
- Weekly ops digest phrasing, long-thread TL;DRs for managers (masked names), application batch summaries for recruiters.
- Fallback: static templates (digest content is data-driven; only phrasing is AI-niced).

## 8. Phase-2 candidates (scoped, not built)
| Candidate | Sketch | Gating condition |
|---|---|---|
| Ask Sewa site assistant | RAG over city/FAQ/housing corpus via SDK embeddings; labeled clearly; logs every answer | organic AEO baseline proven + corpus quality audited |
| CV parsing for ATS | resume → structured skills/experience suggestion in applications screen | recruiter adoption + PII-safe pipeline review |
| Alt-text drafting | media library suggests alt for photos (editor approves) | photography volume making manual alt entry a bottleneck |
| Review-response drafting | suggestions for public review replies (tone-checked) | review velocity making manual replies slow |

## 9. Usage policy (binding summary)
1. AI output never publishes to the public web without a human approving it.
2. AI never communicates with clients directly at launch.
3. No PII/document blobs/credentials to providers (guard suite: [01-ai-architecture.md](01-ai-architecture.md) §5).
4. Every feature has a no-AI fallback; `AI_ENABLED=false` must degrade gracefully everywhere (tested).
5. Model/provider swaps are config, reviewed quarterly against cost/quality ([01-ai-architecture.md](01-ai-architecture.md) §2).
6. Budgets enforced per feature; ops alerted at 80%; hard-stop degrades at 100%.

---

Related: [01-ai-architecture.md](01-ai-architecture.md) · [../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) · [../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md) · [../04-modules/07-blog-news.md](../04-modules/07-blog-news.md) · [../06-content-seo/05-aeo-llm-presence.md](../06-content-seo/05-aeo-llm-presence.md)
