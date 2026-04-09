<p align="center">
<picture>
    <source srcset="https://statamic.com/assets/branding/squircle/statamic-logo-lime-white.svg" media="(prefers-color-scheme: dark)">
    <img align="center" width="350" alt="Statamic Logo" src="https://statamic.com/assets/branding/squircle/statamic-logo-lime.svg">
</picture>
</p>



## 🚀 Scrollytelling Publishing Guide

This project is optimized for high-performance static site generation. Follow these steps to export and publish your stories.

### 1. Build & Generate Sequence
To update the static version of the site, run the following command in your terminal:

```bash
npm run build && APP_ENV=production php artisan statamic:ssg:generate && python3 fix_static.py
```

**What this command does:**
1. **`npm run build`**: Compiles and minifies all CSS/JS assets via Vite.
2. **`ssg:generate`**: Crawls your Statamic site and renders HTML files into the `static/` folder.
3. **`python3 fix_static.py`**: A custom post-processing script that:
   - Injects the correctly hashed Vite links into the HTML files (bypassing SSG tag rendering issues).
   - Mirrors your `public/assets/` folder (images, Lottie animations) into the static build.

### 2. Configuration (`config/statamic/ssg.php`)
You can control which stories are exported by modifying the `urls` array in `config/statamic/ssg.php`:

```php
'urls' => [
    '/',                       // Homepage
    '/first-ever-story',       // Your main story
    '/a-branch-on-paragliding', // Linked branches
],
```

> [!TIP]
> **Relative URLs**: The config is currently set to `use_relative_urls => true`. This ensures that your links work smoothly regardless of whether you host at `domain.com/` or `domain.com/folder/`.

### 3. Previewing Locally
To see exactly how the site will look when published, use a local static server:

```bash
npx serve static
```
Navigate to `http://localhost:3000/first-ever-story`.

### 4. Deployment
Simply upload the entire contents of the `static/` folder to your host of choice:
- **Netlify / Vercel**: Connect your repo or drag-and-drop the `static` folder.
- **GitHub Pages**: Set the `static` directory as the source for your page.
- **Classic FTP**: Upload the folder contents to your `public_html`.

--- 

[docs]: https://statamic.dev/
[discord]: https://statamic.com/discord
[contribution]: https://github.com/statamic/cms/blob/master/CONTRIBUTING.md
[cms-repo]: https://github.com/statamic/cms

