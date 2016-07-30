import cfscrape

scraper = cfscrape.create_scraper()
print scraper.get("http://kissanime.to").content
