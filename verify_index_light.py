from playwright.sync_api import sync_playwright

def run_cuj(page):
    # Enable light mode
    page.emulate_media(color_scheme="light")
    page.goto("http://localhost:8000/index.php")
    page.wait_for_timeout(1000)

    # Take screenshot to verify hero section is back and colors are correct
    page.screenshot(path="/home/jules/verification/screenshots/index_light.png")
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
