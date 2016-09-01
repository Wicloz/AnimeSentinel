import sys
from selenium import webdriver

if __name__ == "__main__":
  driver = webdriver.Firefox()
  driver.get(sys.argv[1])
  print driver.execute_script("return document.documentElement.outerHTML")
