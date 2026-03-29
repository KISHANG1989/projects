import os
from playwright.sync_api import sync_playwright

def capture_all():
    cwd = os.getcwd()
    base_html = os.path.join(cwd, 'AdminPro/html')
    base_docs = os.path.join(cwd, 'AdminPro/documentation')

    pages = [
        ('index.html', base_html),
        ('charts.html', base_html),
        ('tables.html', base_html),
        ('forms.html', base_html),
        ('auth-login.html', base_html),
        ('auth-register.html', base_html),
        ('auth-forgot.html', base_html),
        ('error-404.html', base_html),
        ('error-500.html', base_html),
        ('index.html', base_docs) # Documentation
    ]

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        for filename, base_path in pages:
            file_path = os.path.join(base_path, filename)
            url = f"file://{file_path}"

            # Determine output name
            if base_path == base_docs:
                out_name = "documentation.png"
            else:
                out_name = filename.replace('.html', '.png')

            print(f"Capturing {out_name}...")
            page.goto(url)
            page.screenshot(path=f"verification/screenshots/{out_name}", full_page=True)
            print(f"Saved {out_name}")

        browser.close()

if __name__ == "__main__":
    capture_all()
