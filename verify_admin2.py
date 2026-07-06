from playwright.sync_api import sync_playwright

def run_cuj(page):
    # Try just navigating directly and seeing if we need to login
    page.goto("http://localhost:8000/admin.php")
    page.wait_for_timeout(1000)

    # Check if we are on login page by looking for the user/pass inputs
    if page.locator('input[name="username"]').count() > 0:
        page.fill('input[name="username"]', 'admin')
        page.fill('input[name="password"]', 'admin')
        page.click('button[type="submit"]')
        page.wait_for_timeout(1000)
        page.goto("http://localhost:8000/admin.php")
        page.wait_for_timeout(1000)

    # Take screenshot of the admin area to verify red stripe is gone
    page.screenshot(path="/home/jules/verification/screenshots/admin_fixed.png")
    page.wait_for_timeout(1000)

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            record_video_dir="/home/jules/verification/videos"
        )
        page = context.new_page()
        try:
            run_cuj(page)
        finally:
            context.close()
            browser.close()
