import cfscrape
import sys
import json

if __name__ == "__main__":
  cookie_arg, user_agent = cfscrape.get_cookie_string(sys.argv[1])
  f = open("../../storage/app/cookies/" + sys.argv[2], "w")
  json.dump({'cookies': cookie_arg, 'agent': user_agent}, f)
  f.close()
