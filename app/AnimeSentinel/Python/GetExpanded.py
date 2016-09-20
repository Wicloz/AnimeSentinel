import sys
from selenium import webdriver

if __name__ == "__main__":
  driver = webdriver.PhantomJS(service_log_path='/tmp/ghostdriver.log')
  driver.get(sys.argv[1])
  print driver.execute_script("return document.documentElement.outerHTML")
  driver.quit()
