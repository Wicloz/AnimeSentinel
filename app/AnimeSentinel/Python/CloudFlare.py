import cfscrape
import sys
import json

if __name__ == "__main__":
  cookie_arg, user_agent = cfscrape.get_cookie_string(sys.argv[1])
  print(json.dumps({'cookies': cookie_arg, 'agent': user_agent}))
