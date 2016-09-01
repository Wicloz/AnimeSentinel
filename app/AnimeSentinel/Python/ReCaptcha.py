import sys, re
from time import sleep, time
from random import uniform, randint
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.wait import WebDriverWait
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import NoSuchElementException
from selenium.webdriver.common.desired_capabilities import DesiredCapabilities

def check_exists_by_xpath(xpath):
  try:
    driver.find_element_by_xpath(xpath)
  except NoSuchElementException:
    return False
  return True

def wait_between(a, b):
  rand = uniform(a, b)
  sleep(rand)

def dimention(driver):
  d = int(driver.find_element_by_xpath('//div[@id="rc-imageselect-target"]/table').get_attribute("class")[-1]);
  return d if d else 3  # dimention is 3 by default

# ***** main procedure to identify and submit picture solution ************
def solve_images(driver):
  WebDriverWait(driver, 10).until(
    EC.presence_of_element_located((By.ID ,"rc-imageselect-target"))
  )
  dim = dimention(driver)
  rand3 = randint(0, 1)

  # ****************** click on tiles ******************
  tile1 = WebDriverWait(driver, 10).until(
    EC.element_to_be_clickable((By.XPATH, '//div[@id="rc-imageselect-target"]/table/tbody/tr[{0}]/td[{1}]'.format(randint(1, dim), randint(1, dim))))
  )
  tile1.click()

  tile2 = WebDriverWait(driver, 10).until(
    EC.element_to_be_clickable((By.XPATH, '//div[@id="rc-imageselect-target"]/table/tbody/tr[{0}]/td[{1}]'.format(randint(1, dim), randint(1, dim))))
  )
  tile2.click()

  if (rand3):
    tile3 = WebDriverWait(driver, 10).until(
      EC.element_to_be_clickable((By.XPATH, '//div[@id="rc-imageselect-target"]/table/tbody/tr[{0}]/td[{1}]'.format(randint(1, dim), randint(1, dim))))
    )
    tile3.click()

  # ****************** click on submit buttion ******************
  wait_between(0.3, 0.7)
  driver.find_element_by_id("recaptcha-verify-button").click()
  wait_between(0.3, 0.7)

if __name__ == "__main__":
  print 'Solving reCAPTCHA to unlock page ...'
  start = time()
  url = sys.argv[1]

  # ************* setup webdriver **************
  # dcap = dict(DesiredCapabilities.PHANTOMJS)
  # dcap["phantomjs.page.settings.userAgent"] = (sys.argv[2])
  # driver = webdriver.PhantomJS(desired_capabilities = dcap)
  profile = webdriver.FirefoxProfile()
  profile.set_preference("general.useragent.override", sys.argv[2])
  driver = webdriver.Firefox(profile)

  # ************* load target page **************
  driver.get(url)
  mainWin = driver.current_window_handle

  # move the driver to the first iFrame
  Submit = WebDriverWait(driver, 10).until(
    EC.presence_of_element_located((By.ID, "btnSubmit"))
  )
  driver.switch_to_frame(driver.find_elements_by_tag_name("iframe")[1])

  # ************* locate CheckBox **************
  CheckBox = WebDriverWait(driver, 10).until(
    EC.presence_of_element_located((By.ID, "recaptcha-anchor"))
  )

  # ************* click CheckBox ***************
  wait_between(0.3, 0.7)
  CheckBox.click()

  # ***************** back to main window ******************
  driver.switch_to.window(mainWin)
  wait_between(1.0, 2.0)

  # ************ start solving pictures ******************
  for i in range(0, 33):
    print('{0}-th loop'.format(i + 1))

    # ******** check if checkbox is checked at the 1st frame ***********
    driver.switch_to.window(mainWin)
    driver.switch_to_frame(driver.find_elements_by_tag_name("iframe")[1])
    if check_exists_by_xpath('//span[@aria-checked="true"]'):
      break

    # ********** to the second frame to solve pictures *************
    driver.switch_to.window(mainWin)
    driver.switch_to_frame(driver.find_elements_by_tag_name("iframe")[3])
    solve_images(driver)

  # ************ submit the results ******************
  driver.switch_to.window(mainWin)
  driver.find_element_by_id("btnSubmit").click()
  print 'Submitting ...'
