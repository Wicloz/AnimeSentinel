import sys
import os
from selenium import webdriver

if __name__ == "__main__":
  os.environ["DISPLAY"] = ":99"

  profile = webdriver.FirefoxProfile()
  profile.add_extension(sys.argv[2] + '/addon-1865-latest.xpi')
  driver = webdriver.Firefox(profile)
  driver.set_window_size(1920, 1080)

  driver.get(sys.argv[1])

  print(driver.execute_script("return document.documentElement.outerHTML"))
  driver.close()
  driver.quit()
