import pip
import re
import time

from urllib.parse import urlparse

try:
    from lxml import html
except ImportError:
    pip.main(['install', 'lxml'])

try:
    import requests
except ImportError:
    pip.main(['install', 'requests'])

try:
    from bs4 import BeautifulSoup
except ImportError:
    pip.main(['install', 'BeautifulSoup4'])

class Downloaders():
    def findBetween(self, string, start, end):
        return string[string.find(start) + len(start) : string.rfind(end)]

    def convJStoPy(self, string):
        conv = string.replace("!![]", "1")
        conv = conv.replace("!+[]", "1")
        conv = conv.replace("[]", "0")
        conv = conv.replace("+", "", 0)
        conv = conv.replace("((", "int(str(")
        conv = conv.replace(")+(", ")+str(")
        return conv
        
    def download_kissanime(self, url):
        #begin session
        sess = requests.Session()
        sess.keep_alive = True
        r = sess.get(url, timeout=30.0)
        print("Started session at " + url)
        tree = html.fromstring(r.content)
        script = self.findBetween(r.text, "<script", "</script>")
        strip_script = [stri.strip() for stri in script.splitlines()]

        #We need to decode the javascript
        #f is the last known variable before the messy one
        #starts ln 9
        var_beg = "f, "
        var_end = "="
        json_name_beg = "\""
        json_name_end = "\""
        json_data_beg = ":"
        json_data_end = "}"

        unkwn_var_name = self.findBetween(strip_script[8], var_beg, var_end) + "." + self.findBetween(strip_script[8], json_name_beg, json_name_end) 
        val_unkwn_var = eval(self.convJStoPy(self.findBetween(strip_script[8], json_data_beg, json_data_end) ) )

        js_var_t = "<a href='/'>x</a>"
        #root("/") url
        js_var_t_href = '{uri.scheme}://{uri.netloc}/'.format(uri=urlparse(url))

        if("https://kissanime.to" not in js_var_t_href):
            print(url + "does not go to kissanime.to!")
            return

        js_var_r = re.search(r"https?:\/\/", js_var_t_href).group(0)

        js_var_t = js_var_t_href[len(js_var_r) :]
        js_var_t = js_var_t[0 : len(js_var_t) - 1]

        val_jschl_vc = tree.xpath("//input[contains(@name, 'jschl_vc')]")[0].value
        val_pass = tree.xpath("//input[contains(@name, 'pass')]")[0].value

        #splits code into bite-size array
        #operations are at line 16
        var_complex_op = [stri+";" for stri in strip_script[15].split(";")]

        for string in var_complex_op[:-2]:
            if(unkwn_var_name not in string):
                continue
            else:
                eval_phrase = "val_unkwn_var" + self.findBetween(string, unkwn_var_name, "=") + "(" + self.convJStoPy(self.findBetween(string, '=', ';') ) + ")"
                #print(eval_phrase)
                #print("genned val: " + str(eval("(" + self.convJStoPy(self.findBetween(string, '=', ';') ) + ")") ) )
                val_unkwn_var = eval(eval_phrase)

        val_unkwn_var = val_unkwn_var + len(js_var_t)

        payload = {
            'jschl_vc' : val_jschl_vc,
            'pass' : val_pass,
            'jschl_answer' : val_unkwn_var
        }

        print("Waiting for authentication...")
        #wait for 4 sec
        time.sleep(4)

        URL_SEND_PAYLOAD_TO = "https://kissanime.to/cdn-cgi/l/chk_jschl"
        sess.get(URL_SEND_PAYLOAD_TO, params=payload, timeout=30.0)

        r = sess.get(url, timeout=30.0)

        URL_ERROR_URL = "https://kissanime.to/Error"
        if(r.url == URL_ERROR_URL):
            print("Url error at " + url)
            print("Check your url and try again")
            return

        if(r.status_code != requests.codes.ok):
            print("Error: HTTP RESPONSE CODE: " + str(r.status_code))
            return

        print("Success!")
        #ASSUMING PAGE IS LOADED STARTING HERE
        soup = BeautifulSoup(str(r.content).replace('\\r\\n', ''), 'html.parser');
        return soup;
