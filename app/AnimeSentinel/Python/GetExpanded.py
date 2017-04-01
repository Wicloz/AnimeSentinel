import sys
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

if __name__ == "__main__":
  os.environ["DISPLAY"] = ":99"

  profile = webdriver.FirefoxProfile()
  # profile.set_preference('webdriver.load.strategy', 'unstable')
  # profile.set_preference('http.response.timeout', 1)
  # profile.set_preference('dom.max_script_run_time', 1)
  # profile.set_preference('dom.max_chrome_script_run_time', 1)
  profile.add_extension(sys.argv[2] + '/addon-1865-latest.xpi')
  driver = webdriver.Firefox(profile)
  driver.set_window_size(1920, 1080)

  driver.get(sys.argv[1])
  # wait = WebDriverWait(driver, 10)
  # wait.until(EC.visibility_of_element_located((By.CSS_SELECTOR, ".razrada")))

  print(driver.execute_script("return document.documentElement.outerHTML"))
  driver.close()
  driver.quit()
