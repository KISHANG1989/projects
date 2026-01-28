# Hosting Guide for AdminPro

AdminPro is a static HTML/CSS/JS template, which makes it very easy to host. However, because it uses external JSON files for Internationalization (i18n), **you cannot simply open the `index.html` file directly from your file system** (e.g., `file:///C:/Users/...`). Browsers block this for security reasons (CORS policy).

You must serve the files via a web server.

## 1. Local Development (Testing on your PC)

To view the template correctly on your computer, use a local server:

### VS Code (Recommended)
1. Install the **Live Server** extension by Ritwick Dey.
2. Open the `AdminPro` folder in VS Code.
3. Right-click `index.html` and select **"Open with Live Server"**.

### Python
If you have Python installed, open your terminal in the `AdminPro` folder and run:
```bash
# Python 3
python -m http.server 8000
```
Then visit `http://localhost:8000`.

---

## 2. GitHub Pages (Free Hosting)

GitHub Pages is great for hosting static sites for free.

1. Create a new repository on GitHub (e.g., `my-admin-dashboard`).
2. Push the contents of the `AdminPro` folder to this repository.
   - Ensure `index.html` is in the root of the repository.
3. Go to **Settings** > **Pages**.
4. Under **Source**, select `main` (or `master`) branch and `/ (root)` folder.
5. Click **Save**.
6. Your site will be live at `https://<your-username>.github.io/my-admin-dashboard/`.

---

## 3. Netlify (Drag & Drop)

Netlify is the easiest way to host static sites.

1. Go to [netlify.com](https://www.netlify.com/) and sign up.
2. Log in and go to the **Sites** tab.
3. Drag and drop the `AdminPro` folder into the "Drag and drop your site output folder here" area.
4. Netlify will upload and deploy it instantly. You will get a unique URL (e.g., `https://admiring-turing-12345.netlify.app`).

---

## 4. Standard Web Server (cPanel, Apache, Nginx)

If you have a traditional web hosting plan (e.g., GoDaddy, Bluehost):

1. Connect to your server using an FTP client (like FileZilla) or use the cPanel File Manager.
2. Navigate to your `public_html` directory (or the specific subdomain folder).
3. Upload all files and folders from `AdminPro` (`assets`, `html`, `languages`, `index.html`, etc.).
4. Your site will be accessible via your domain name.

---

## Important Note on "Base URL"

If you host the site in a subdirectory (e.g., `example.com/dashboard/`), ensure your links in the HTML are relative (which they are by default in AdminPro) or update them to absolute paths if you encounter issues.
