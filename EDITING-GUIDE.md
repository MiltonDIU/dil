# Editing the DILC website

A one-page guide for staff. Everything on the public site is editable here —
you never need a developer to change text, images, menus, forms or colours.

**Admin address:** `https://YOUR-DOMAIN/admin`
Sign in with your DIU email address and password.

---

## The sidebar, at a glance

| Group | What lives there |
|---|---|
| **Content** | Courses, Pages, News, Categories |
| **Appearance** | Site settings, Menus, Homepage blocks, Slider |
| **Forms** | Your forms, and the messages people send |
| **System** | Redirects, Users, Media library, AI decisions |

Press **⌘K** (Mac) or **Ctrl+K** (Windows) to search everything at once.

---

## Courses

**Content → Courses.** This is the heart of the site.

Each course has four tabs:

- **Content** — title, web address, short summary, and the full description.
- **Structure & fees** — language, CEFR level, duration, number of classes, and
  the three fee tiers (Regular / Daffodil family / Outside students).
- **Publishing** — whether it is live, its image, and its position in the list.
- **SEO** — how the page appears in Google.

**A blank fee or level is hidden on the website**, not shown as empty. Leave a
field blank rather than typing "N/A".

**Reordering:** drag rows on the Courses list. That order is what visitors see.

**The tabs at the top of the list** are shortcuts:
*Needs content* shows courses with no description; *Fee missing* shows courses
with no price. Both need your attention — they came across from the old site
that way.

---

## Pages

**Content → Pages.** About, Contact, Mission & Vision, Director Message and so on.

- **Title** is the heading; **Slug** is the web address (`/about`).
- **Changing a slug changes the URL.** Old links will break unless a redirect
  is added (System → Redirects).
- The **Original import** tab shows the untouched text from the old Joomla site.
  It is read-only and kept permanently, so nothing is ever lost.

> The **About** page currently contains text about the DIU Office of Students'
> Affairs. That came from the old site and is not about DILC. Please replace it.

---

## News

**Content → News.** Works like Pages, with a date and an optional image.
The News section starts empty because both old news articles were in the
Joomla trash. Add a post and publish it to bring the section to life.

---

## The homepage

**Appearance → Homepage blocks.** The homepage is built from stacked blocks.
Drag to reorder; toggle *Published* to hide one without deleting it.

| Block type | What it shows |
|---|---|
| Slider | The image carousel at the top (edit the images under **Slider**) |
| Rich text | A heading and formatted text, with an optional button |
| Feature cards | The row of course cards |
| Video | A YouTube video — just paste the normal watch link |
| Link list | A simple list of links (Downloads, Our Partners) |
| Map embed | The Google Maps panel |

**Buttons only appear when they have a link.** Leave the link blank and the
button is hidden rather than going nowhere.

---

## The slider

**Appearance → Slider.** Upload an image, optionally add a caption and a button.

Always fill in **Image description (alt text)** — it is what screen readers
announce and what Google reads. Rows where it is missing are highlighted.

---

## Menus

**Appearance → Menus → Main Menu → Menu items.**

Drag to reorder. Indented rows (shown with `↳`) are dropdown children.

When adding an item, choose what it links to:

- **Page / Course / News article** — pick the record; the link stays correct
  even if you later change its web address.
- **Built-in page** — Homepage, All courses, News.
- **Custom link** — any other address.
- **Dropdown heading** — a top-level label that is not clickable and only opens
  its children.

The **Target has content** column warns you when a menu item points at a page
with nothing on it.

---

## Site settings

**Appearance → Site settings.** Six tabs:

- **Identity** — site name, logo, favicon, copyright line.
  Type `{year}` in the copyright and it stays current automatically.
- **Contact** — address, phone, email, Google Maps embed. These feed the footer
  and the contact page.
- **Social links** — paste the full address (`https://facebook.com/...`).
  **Blank means the icon is hidden**, which is why none show right now.
- **Colours** — the site's brand colours. Changes are live immediately.
- **SEO** — the default Google description, share image and Google Analytics ID.
- **Features** — which form appears on the contact page and course pages.

---

## Forms

**Forms → Forms.** Two forms ship ready to use: **Contact** and
**Course Enquiry**.

To change a form, open it and scroll to **Fields**. You can add, rename,
reorder (drag) and delete fields with no developer involvement. For each field:

- **Type** — text, email, phone, paragraph, dropdown, date, file upload…
- **Required** — whether it must be filled in.
- **Choices** — for dropdowns. Or set **Or fill choices from → Published
  courses**, and the list updates itself whenever you publish a new course.

**Notify these addresses** decides who is emailed on each submission.

### Reading messages

**Forms → Submissions.** A number badge appears in the sidebar when something
is new. Click a row to read it, use the tabs to filter New / Read / Replied /
Spam, and select rows to change status in bulk or **Export CSV** for Excel.

Submissions are stored even if the notification email fails, so nothing is lost.

---

## Redirects

**System → Redirects.** When someone visits an address from the old website,
this sends them to the right new page — which is what protects the site's
Google ranking.

55 were created automatically. Add one whenever you change a page's web
address: put the old address in **Source** and the new one in **Target**.

The **Hits** column shows which old links people still use.

---

## Media library

**System → Media library.** Everything uploaded to the site. You can add alt
text here. The **On disk** column flags a file that has gone missing.

---

## AI decisions

**System → AI decisions.** Course fees, durations and Google descriptions were
extracted automatically from the old site's text and should be spot-checked.

If one is wrong, correct it and tick **Reviewed and approved** — that locks it,
so no future automated run will overwrite your correction.

---

## Things to do first

1. Replace the **About** page text (it is about the wrong department).
2. Add content to the five empty items — see the **Needs content** tab on
   Pages and Courses. **Bangla Language** is the most urgent: it is in the
   main menu already.
3. Fill in the five **social links** in Site settings.
4. Add fees to the four courses that have none.
5. Add the **Google Analytics ID** if you track visitors.

---

## Two habits worth keeping

- **Unpublish rather than delete.** Toggling *Published* off hides something
  from the website while keeping it recoverable. Deleting is permanent.
- **Alt text on every image.** One short sentence describing the picture. It
  helps blind visitors and it helps Google.
