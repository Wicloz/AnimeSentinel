import cfscrape
import sys
import json
import os

if __name__ == "__main__":
  cookie_arg, user_agent = cfscrape.get_cookie_string(sys.argv[1])
  if not os.path.exists("../storage/app/cookies"):
    os.makedirs("../storage/app/cookies")
  f = open("../storage/app/cookies/" + sys.argv[2], "w")
  json.dump({'cookies': cookie_arg, 'agent': user_agent}, f)
  f.close()
  print(json.dumps({'cookies': cookie_arg, 'agent': user_agent}))
