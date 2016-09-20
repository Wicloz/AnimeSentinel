import sys
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.wait import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException

if __name__ == "__main__":
  driver = webdriver.PhantomJS(service_log_path='/tmp/ghostdriver.log')
  driver.get(sys.argv[1])

  while True:
    # scroll to bottom of the page
    driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")

    try:
      # wait for spinner to appear
      WebDriverWait(driver, 2).until(
        EC.visibility_of_element_located((By.ID, "loading-spinner"))
      )
    except TimeoutException:
      # if it doesn't appear, the bottom has been reached
      break

    # wait for spinner to disappear
    WebDriverWait(driver, 10).until(
      EC.invisibility_of_element_located((By.ID, "loading-spinner"))
    )

  print driver.execute_script("return document.documentElement.outerHTML")
  driver.quit()
