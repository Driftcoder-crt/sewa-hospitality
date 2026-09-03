# 01 — Reference Site Map & Page-by-Page Inventory

**Complete inventory of the reference platform (www.formulaindia.com, mirrored 2026-08-31). Every page, every section, every element. Nothing ignored.** Purpose: guarantee the Sewa build covers 100% of this surface before adding anything new.

The reference is actually **three systems**: a Next.js 11 corporate site, a WordPress 6.8 blog at `/blog/`, and a CodeIgniter (PHP) client portal at `/login/` — glued by a REST API at `api.formulaindia.com`.

---

## 1. Complete site map

```
/                                        Homepage (Next.js)
/about-us/                               About: mission, who we are, values, 15 leaders
/services/                               Services hub (11 cards)
/services/employee-mobility/             Family page (6 children)
   /relocation/                          Accordion: orientation, home search, school search,
                                         tenancy mgmt, departure program
   /immigration/                         3 child links ↓
   /serviced-apartments/                 City accordion: New Delhi, Gurugram, Chennai
                                         (deep-links OUT to sister site)
   /moving-services/                     Local/domestic/international HHG, pets, workplace
   /corporate-housing/                   Options, centralised bookings, verified, payments
   /fleet/                               Long-term, daily, self-drive + 24×7 helpline
                                         (SEO links OUT to sister car-rental site)
   /wellbeing-support/                   → 500 server error (broken in production)
/services/business-mobility/             Family page (5 children)
   /travel/                              IATA travel division (text only)
   /business-space/                      Office, industrial land, warehouses, factories
   /requirement/                         "Recruitment": search, RPO, HR policies
   /interior-designing/                  Text only
   /sanitization/                        Home, office, fleet sanitization
/services/immigration/                   Immigration hub (3 children)
   /inbound-immigration-services/        Resident permit, extensions, conversions, exit
                                         permits, OCI
   /outbound-immigration-services/       Employment/dependent visas, consular, work permits,
                                         legalization
   /ancillary-services/                  PAN card
/technology/                             "MobiRelo" product: 9 feature cards + 3 capability
                                         blocks (co-worker / client HR / internal)
/careers/                                6 job accordions + apply modal (resume upload) +
                                         39-photo Swiper gallery with lightbox
/clients-speak/                          24 static testimonial cards
/contact-us/                             Form (7 fields incl. service select) + 9 city
                                         office tabs with Google Maps embeds
/csr/                                    NGO partner rows (7 NGOs) + 11-photo gallery
/news/                                   3 news posts + sidebar (recent/newsletter/social)
/news/{slug}/                            3 articles: Sri Lanka visa-free, Sri Lanka 5-yr
                                         visa, Kuwait visa suspension
/login/                                  PHP portal: login box + forgot-password box
/privacy-policy/  /terms-conditions/  /disclaimer/
/awards-milestone/                       Route exists in build manifest; hidden/unlinked
/404  /500  /thankyou                    System routes (thankyou = form success target)
/blog/                                   WordPress blog (see §5)
   /blog/page/2..5/                      Pagination (45 posts, 10/page)
   /blog/{YYYY}/{MM}/{DD}/{slug}/         Post URLs
   /blog/category/{slug}/ (+ nested, + page/2..4)
   /blog/tag/{slug}/
Foreign: formulaindia.co.jp (Japan site, header flag button)
Sister: formulahousing.com, movewithformula.com, formulaservicedapartment.com,
        formulacarrental.com, thetravelformula.com, suraksha4u.com
```

## 2. Global layout (every page)

**Header:** logo (light/dark variants) · hamburger-only navigation (slide-out sidenav, 320px) · Japan flag button. Nav tree:
- Home · **This Is Us** (Mission, Who We Are, Formula Values → /about-us/; CSR) · **Our Solutions** (Employee Mobility, Business Mobility, All Services) · Technology · Careers · Clients Speak · Blog · News · Connect · social icons (Facebook, Twitter, Instagram, LinkedIn, YouTube)

**Footer:** logo · Company links (This Is Us, Careers, Blog, Clients Speak) · Terms & Policies (Privacy, Disclaimer, T&C) · Need Help? (Login, Connect) · Follow Us (5 social) · **20 membership/partner logos** (EuRA full member, ISO, Worldwide ERC, IAM, iBANet, CHPA, FICCI, JCC, Centuro Global, PHD Chamber, Ministry of Tourism, ISO 27001, ISO 14001, IFCCI, IATA, GMJ, GME, Paradigm, partners strip) · copyright "Formula Corporate Solutions India Pvt. Ltd."

**Group Ventures strip** (above footer, every page): 5 outbound cards → corporate housing, moving, serviced apartments, car rental, travel sister sites (login adds Suraksha as 6th).

## 3. Page-by-page detail

### 3.1 Homepage
1. **Hero** — 3-column triptych: Employee Mobility (image + H1 overlay → employee page) · animated "3R" GIF banner (center, no link) · Business Mobility (image + H1 overlay → business page). Desktop/mobile image swaps. Chevron/mouse smooth-scroll-down indicators. 100vh height.
2. **WHO WE ARE** — 4 paragraphs: corporate mobility provider, Fortune-500 clients inbound/outbound, human-centric technology platform, empathy for relocation challenges.
3. **Our Solutions** — 2 cards (Employee/Business Mobility) with API-served images + Read more.
4. **CTA band** — "Not sure which solution fits your business needs?" + Connect button.
5. **Video block** — poster image + play → Bootstrap modal with YouTube embed.
6. **Counters** — 20000+ Happy Clients · 25+ Cities · 99% Customer satisfaction (animated count-up).
7. **Clients Speak** — 4 testimonials (name, service, city) + View All →.
8. **Group Ventures** + Footer.

### 3.2 About Us
Mission → Who We Are (4 paragraphs, same as home) → Formula Values (values shown only as a graphic — no accessible text) → CSR teaser (fisherman's proverb) → **Our Leadership: 15 leader cards** (Managing Director Raman Narula with founding story since 2004, CEO, and 13 directors/heads with bios, served via API) with hover-overlay bios → CTA band.

### 3.3 Services pages
Covered in full in [03-service-catalog.md](../01-platform-vision/03-service-catalog.md). Key patterns: API-image hero + big H1 · intro paragraph · card grids on hub pages · **accordion content duplicated twice in DOM** (desktop + mobile copies — a maintenance and SEO defect) · CTA band. Immigration has a separate 3-page sub-tree. Serviced Apartments and Fleet link out to sister sites (losing the visitor + splitting SEO).

### 3.4 Technology (MobiRelo)
Product pitch page: 9 feature icon cards (Secured Platform, Virtual Tours, Digital City Guide, Interactive Dashboard, Document Management, Digital Itinerary, Tenancy Management, Instant access to consultant, Track real-time progress) · "Technology Capability" 3 blocks: Co-Worker experience, Client HR/Mobility experience, Internal delivery · CTA "request a demo" → contact. (Sewa equivalents in [../04-modules/04-client-portal.md](../04-modules/04-client-portal.md) — real product, not just a pitch page.)

### 3.5 Careers
Life @ Formula text + wide image · "we're hiring" with enquiry email · **6 job accordions** (title, experience years, location): Japan Desk CSO (N2/N3 Japanese, Chennai & Bengaluru), Destination Consultant (5 cities), Visa Officer (3 cities), Facility Executive/TMS (Mumbai), Account Manager–Relocation (Bengaluru), Accounts-cum-Admin (Mumbai) · each has **Apply Now → modal form** (fullname, email, mobile, resume file, message) → posts to `api/postcareers` → redirect /thankyou · **39-photo gallery** (Swiper autoplay + Fancybox lightbox). Job detail URLs exist (slug-per-job) but render 404 publicly.

### 3.6 Contact Us
**Form:** First Name*, Last Name*, Mobile*, Email*, Subject* (select of 11 services), Message* → `api/contactus` → /thankyou. **9 city office tabs** (New Delhi corporate office, Chennai, Bengaluru, Ahmedabad, Mumbai, Pune, Hyderabad, Surat, Gurugram), each with address card + Google Maps iframe; shared call +91-9650003642 and enquiry@formulaindia.com; Mumbai has an added landline. → Sewa offices + plans in [../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md).

### 3.7 Clients Speak
24 static cards: quote, service (Destination Services, Serviced Apartment, Fleet, Immigration, Moving), city (Chennai, Gurugram, Pune, Bengaluru, Mumbai, Delhi, Hyderabad). No pagination, no review source, no dates.

### 3.8 CSR
Intro (empathy, "lead by example") + 11-photo Swiper gallery + **7 NGO partner rows** (logo, outbound site link, description): Shikhar (vocational training for girls, Delhi), Sapna (healthcare, Alwar), The Earth Saviours Foundation (senior citizens), Aashray (children's home, Mahbubnagar), Cansupport (cancer palliative care), New Hope And New Life (orphanages/tuition), ADP NGO (disabled people).

### 3.9 News
Listing: 3 cards (image left, date, title, excerpt) + sidebar (Recent News thumbnails, **Newsletter form wired to action="#" — non-functional**, Connect With Us). 3 articles (May 2024 Sri Lanka visa-free for Indians; Dec 2021 Sri Lanka 5-year visas; Dec 2021 Kuwait visa suspension). Detail pages use placeholder API meta values ("metatitle", "keyword", "descriptions" — literally). **No JSON-LD on news pages.**

### 3.10 Login portal (CodeIgniter)
"Formula Client Dashboard" login page: **login form** (email, password, forgot link) → AJAX `POST /login/login` → JSON `{status:1}` → dashboard.html (auth-protected, not crawled) · **forgot password** → `POST /login/forgetpassword`. Session: `ci_session` cookie. Stack: Bootstrap 4 + jQuery 3.6 + jquery-validate 1.19.3. Errors shown inline for 3 seconds.

### 3.11 Legal pages
Privacy Policy (+ separate cookies policy) · Terms & Conditions (+ cancellation/refund policy + shipping/delivery policy) · Disclaimer (+ copyright notice). All owner-branded to the legal entity with registered office address.

## 4. System pages
- **404:** "Oops! You're lost" + Go to Home button + 404 illustration.
- **/thankyou:** form-success redirect target (exists in build manifest; not otherwise rich).
- **/awards-milestone:** route present, unlinked from nav.
- **404-as-content:** `apple-touch-icon` request mirrors as a 404 page; `wellbeing-support` service page returns HTTP 500.

## 5. Blog (WordPress)
- **45 posts** 2019–2026 · URL pattern `/blog/YYYY/MM/DD/{slug}/` · 10 per page (5 pages).
- **15 categories** (12 top-level + 3 nested): Expat News (16), Lifestyle in India (11), Relocation (10, + child Moving 4), Visa & Immigration News (7), Corporate Housing (6), Health & Safety (5, + legacy child Visa & Immigration 2), Global Mobility (3), Fleet (2), News (2), Uncategorized (2), Expat in India (1, + child Relocation 2), Relocation Guide to India (1).
- **10 tags** with counts; tag pages exist; sidebar renders the site-wide tag cloud on posts instead of that post's tags.
- **Listing page:** hero "Blog" + post cards (image, date badge, H2 title, ~50-word excerpt, Continue Reading, categories, date) + sidebar: Search, Connect With Us, Recent Posts, Tag Cloud + CTA band + ventures + footer.
- **Post page:** **two H1s** (hero bg-text + content H1) · meta row (categories, 0 comments, date — no author byline) · body with H2/strong pseudo-headings · prev/next post boxes · comment form (0 comments everywhere; author = WP user "admin") · same sidebar + CTA.
- **Content mix:** COVID-19 updates (2020–21), an 8-post city-guide series (Mumbai, Bengaluru, Delhi, Ahmedabad, Hyderabad, Chennai, Pune, GIFT City), immigration/visa explainers (FRRO, OCI portal, Immigration & Foreigners Bill 2025), corporate-housing guides, relocation-trends posts, brand posts. One 2026 post is set to `noindex` (accidentally, evidently).
- **SEO by Rank Math:** full metadata, OG/Twitter, Organization/WebSite/CollectionPage/BlogPosting+Person("admin") JSON-LD, canonicals, prev/next. Full patterns in [05-seo-content-analysis.md](05-seo-content-analysis.md).

## 6. Media & assets inventory (what exists to conceptually replace)

- `images/` (~84 files): logo variants, hero triptych images + animated 3R GIFs (desktop/mobile), video poster, per-page banners (about, career, csr, blog, news, contact, policy, disclaimer, services-list, mobi-banner), MobiRelo 9 feature icons, 20 membership logos, 7 NGO logos, CSR gallery set, Japan flag, favicon.
- `images/venturs/` (5 sister-site cards), `images/partners/` (15 partner logos), `images/img/arrow-select.svg`.
- API media CDN: MD5-named images in `media/{,thumb,medium,large}/` (179 files) — leader photos, service heroes, testimonial-adjacent content, career gallery (39), news thumbnails.
- Blog uploads: ~265 media files (2019–2026), srcset sizes.
- Webfonts: Poppins (body, full weight range), Bebas Neue (headings), Montserrat (secondary) + Font Awesome 5 + a tiny custom icon font (5 socials).

## 7. Coverage checklist for the Sewa build

Every item above must appear (re-specified) in the Sewa platform. Quick map:

| Reference element | Sewa doc |
|---|---|
| Homepage sections 1–8 | [../05-design-system/03-ux-interactions.md](../05-design-system/03-ux-interactions.md) + CMS blocks |
| 11 services + immigration sub-tree | [../01-platform-vision/03-service-catalog.md](../01-platform-vision/03-service-catalog.md) |
| Leadership/About | [../04-modules/06-hr-employee-module.md](../04-modules/06-hr-employee-module.md) |
| Careers + apply modal | [../04-modules/06-hr-employee-module.md](../04-modules/06-hr-employee-module.md) |
| Contact + 9 offices | [../04-modules/03-leads-crm.md](../04-modules/03-leads-crm.md) |
| Client portal (login) | [../04-modules/04-client-portal.md](../04-modules/04-client-portal.md) |
| MobiRelo pitch | [../04-modules/04-client-portal.md](../04-modules/04-client-portal.md) (real portal replaces pitch) |
| Blog + news | [../04-modules/07-blog-news.md](../04-modules/07-blog-news.md) |
| Testimonials | [../04-modules/08-testimonials-reviews.md](../04-modules/08-testimonials-reviews.md) |
| CSR | [../04-modules/09-csr-module.md](../04-modules/09-csr-module.md) |
| Legal pages | CMS + [../03-technical-specs/05-security-reliability.md](../03-technical-specs/05-security-reliability.md) (privacy/cookies) |
| Group ventures strip | [../01-platform-vision/04-subdomains-ventures.md](../01-platform-vision/04-subdomains-ventures.md) (Sewa keeps visitors on-domain) |
| Membership strip | [../07-marketing-trust/03-trust-authority.md](../07-marketing-trust/03-trust-authority.md) |
| Japan flag | Locale system, [../04-modules/11-multilingual.md](../04-modules/11-multilingual.md) |
| 404/500/thankyou | [../03-technical-specs/13-testing-qa.md](../03-technical-specs/13-testing-qa.md) + CMS |
| Counters (20000+/25+/99%) | CMS "stats" block (honest values, editable) |

---

Related: [02-components-interactions.md](02-components-interactions.md) · [03-api-and-data-layer.md](03-api-and-data-layer.md) · [04-design-brand-analysis.md](04-design-brand-analysis.md) · [05-seo-content-analysis.md](05-seo-content-analysis.md) · [06-weaknesses-opportunities.md](06-weaknesses-opportunities.md)
