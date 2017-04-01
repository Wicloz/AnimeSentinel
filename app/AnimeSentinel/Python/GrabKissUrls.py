import sys
import json
from pip._vendor.pyparsing import Regex
from KissNetwork.kissheaders import Headers

RE_URL_OL = Regex(r'src\=["\'](https?://o(?:pen)?load.+?)["\']')
RE_URL_SM = Regex(r'src\=["\'](https?://stream\.moe/embed.+?)["\']')

########################################################################################
if __name__ == "__main__":
  print(json.dumps([{'url': 'test', 'resolution': '0x0'}, {'url': 'test2', 'resolution': '1x1'}]))

########################################################################################
def PlayVideo(url, m, **kwargs):
    """
    Get Video URL
    Currently available host: GoogleVideo, Openload
    GoogleVideo links have the potential for multiple resolutions links
    Stream.moe:
        - used to be supported, but host site is currently offline
        - leaving code for now as it does not affect playback
    Openload, and Stream.moe give only one link (the highest), so no optional resolutions

    Video URL fallback system.
    Order as follows:
    * Preferred Server = KissNetwork
        KissNetwork --> Openload --> Stream.moe
    * Preferred Server = Openload
        Openload --> Stream.moe --> KissNetwork
    * Preferred Server = Stream
        Stream.moe --> KissNetwork
    """

    req_url = url + ('&s={}'.format(Prefs['server'].lower()) if Prefs['server'] != 'KissNetwork' else '')
    vurl = get_video_url(url, m, *setup_video_page(req_url))

    if (not vurl) and (Prefs['server'] == 'KissNetwork'):
        req_url = url + '&s=openload'
        vurl = get_video_url(url, m, *setup_video_page(req_url))

    if (not vurl) and (req_url.endswith('openload')):
        req_url = url + '&s=stream'
        vurl = get_video_url(url, m, *setup_video_page(req_url))

    if (not vurl) and (req_url.endswith('stream')) and (Prefs['server'] != 'KissNetwork'):
        vurl = get_video_url(url, m, *setup_video_page(url))

    if Prefs['force_redirect'] and (Prefs['force_transcode'] == False) and (Prefs['server'] == 'KissNetwork'):
        Log.Debug('* Force Redirect ON')
        Log.Debug('* Note: Videos will NO longer play outside the network connection.')
        try:
            vurl = get_url_redirect_v2(vurl)
            if 'googlevideo' in vurl and not vurl == False:
                Log.Debug('* URL Redirect       = {}'.format(vurl.split('?')[0] + '...'))
            else:
                Log.Debug('* URL Redirect       = {}'.format(vurl))
        except:
            Log.Exception('* URL Redirect faild. Returning PlayVideo URL instead')
    else:
        Log.Debug('* Force Redirect OFF')

    Log.Debug('*' * 80)

    if vurl:
        if Prefs['server'] == 'Beta':
            http_headers = {'User-Agent': Common.USER_AGENT, 'Referer': req_url}
            return IndirectResponse(VideoClipObject, key=vurl, http_headers=http_headers)
        return IndirectResponse(VideoClipObject, key=vurl)

    raise Ex.MediaNotAvailable

########################################################################################
def setup_video_page(url):
    r = NRequest(url, raw=True)
    headers = Headers.get_headers_for_url(url)
    dc = headers['cookie']
    if 'k_token' in r.cookies:
        headers.update({'cookie': '; '.join([dc, 'k_token={}'.format(r.cookies['k_token'])])})
    return headers, r.text

########################################################################################
def get_video_url(url, m, headers, page):
    ol = RE_URL_OL.search(page)
    st = RE_URL_SM.search(page)
    if ol:
        Log.Debug('* OpenLoad URL       = {}'.format(ol.group(1)))
        vurl = get_openload_url(ol.group(1))
    elif st:
        Log.Debug('* StreamMoe URL      = {}'.format(st.group(1)))
        vurl = get_streammoe_url(st.group(1))
    else:
        vurl = get_googlevideo_url(page, url, m, headers)
    return vurl

####################################################################################################
def get_googlevideo_url(page, url, m, headers):
    """
    Get GoogleVideo URLs
    Returns the Hights stream playable depending on the previous Stream Selections
    If Stream not found, then try's to find next hightest.
    Example 1: format list = [1080p, 720p, 360p]
        If 480p was previously chosen, then 720p will be used
    Example 2: format list = [720p, 480p, 360p]
        If 1080p was previously chosen, then 720p will be used
    """

    html = HTML.ElementFromString(page)
    sQual = Regex(r'(id\="slcQualix")').search(page)
    olist = html.xpath('//select[@id="%s"]/option' %("slcQualix" if sQual else "selectQuality"))
    type_title = Common.GetTypeTitle(url)
    type_title_lower = type_title.lower()
    if not olist:
        Log.Error('* This Video is broken, Kiss{} is working to fix it.'.format(type_title))
        raise Ex.MediaNotAvailable

    vurl = False
    vurls = list()
    # format info taken from here:
    # https://github.com/rg3/youtube-dl/blob/fd050249afce1bcc9e7f4a127069375467007b55/youtube_dl/extractor/youtube.py#L281
    # mp4 {format: resolution} dictionary
    fmt_dict = {'37': 1080, '22': 720, '59': 480, '78': 480, '18': 360}
    if Prefs['force_transcode']:
        # When force transcoding, then provide support for webm and flv video resolutions
        # webm {format: resolution} dictionary
        fmt_dict.update({'43': 360, '44': 480, '45': 720, '46': 1080})
        # flv {format: resolution} dictionary
        fmt_dict.update({'35': 480, '34': 360})
    # reversed mp4 format dictionary, paired values with resolutin selection in MediaObjectsForURL()
    rfmt_dict = {'1080': 37, '720': 22, '480': 59, '360': 18}

    enc_test = RE_ENC_TEST.search(page)
    if enc_test:
        Log.Debug('* {}'.format(enc_test.group(1)))

    for node in olist:
        if enc_test:
            post_data = None
            if (type_title_lower != 'anime'):
                post_data = {'krsk': dmm(type_title_lower)}
            #Log.Debug("* post_data = {}".format(post_data))

            rks_init = get_rks_init(type_title_lower, url, headers, post_data)
            #Log.Debug("* rks_init = {}".format(rks_init))
            key = get_rks(type_title_lower, page, rks_init)
            #Log.Debug("* key = {}".format(key))
            vurl_old = KissDecrypt.decrypt(node.get('value'), type_title_lower, key)
        else:
            vurl_old = String.Base64Decode(node.get('value'))

        if ('googlevideo' in vurl_old) or ('blogspot.com' in vurl_old):
            try:
                itag = vurl_old.split('=m')[1]
                vurls.append((vurl_old, fmt_dict[itag]))
            except:
                itag = 'No itag Found!'
                itag_test = RE_ITAG.search(vurl_old)
                if itag_test:
                    itag = str(itag_test.group(1))
                    if itag in fmt_dict.keys():
                        vurls.append((vurl_old, fmt_dict[itag]))
        else:
            try:
                res = node.text.strip()[:-1]
                itag = str(rfmt_dict[res])
                vurls.append((vurl_old, int(res)))
            except Exception as e:
                itag = u'No itag Found: {}'.format(e)

        if not itag in fmt_dict.keys():
            Log.Warn('* Format NOT Supported: {}'.format(itag))

    if vurls:
        Log.Debug('* pre resolution selected = {}'.format(m))
        for item, mm in Util.ListSortedByKey(vurls, 1):
            vurl = item
            nm = rfmt_dict[str(mm)]
            if nm == int(m[1:]):
                #Log.Debug('* Selecting {}p stream'.format(mm))
                break
            elif mm > fmt_dict[m[1:]]:
                #Log.Debug('* Selecting {}p stream'.format(mm))
                break
        Log.Debug('* Selecting {}p stream'.format(mm))

    if ((type_title_lower == 'cartoon') or (type_title_lower == 'drama') and ('Play?' in vurl)):
        Log.Debug(u"* Trying to get {} Redirect Link for '{}'".format(type_title_lower, vurl))
        headers['referer'] = url
        vurl = get_url_redirect_v2(vurl, headers)

    return vurl

####################################################################################################
def get_openload_url(url):
    """
    Get OpenLoad URLs
    Code returns stream link for OpenLoad videos
    """

    http_headers = {'User-Agent': Common.USER_AGENT, 'Referer': url}
    ourl = OpenloadStreamFromURL(url, http_headers=http_headers)
    if ourl:
        rourl = get_url_redirect_v2(ourl, http_headers)
        return rourl

    Log.Error(u"* OpenloadStreamFromURL: cannot parse for stream '{}'".format(url))
    return False

####################################################################################################
def get_streammoe_url(moe_url):
    """Get Stream.moe URLs"""

    try:
        page = HTTP.Request(moe_url, cacheTime=CACHE_1MINUTE).content
    except:
        Log.Exception('* StreamMoe Error: >>>')
        return False

    r = RE_ATOB.search(page)
    if r:
        html_text = String.Base64Decode(r.group(1))
        html = HTML.ElementFromString(html_text)

        vurl = html.xpath('//source/@src')
        if vurl:
            return vurl[0]

    return False

####################################################################################################
def get_url_redirect_v2(input_url, http_headers=None):
    """URL Redirect V2 using requests.head"""

    if not http_headers:
        http_headers = {'User-Agent': Common.USER_AGENT, 'Referer': input_url}

    r = requests.head(input_url, headers=http_headers)
    if 'location' in r.headers.keys():
        return r.headers['location']
    elif 'Location' in r.headers.keys():
        return r.headers['Location']

    Log.Debug(u"* URL Redirect: No Redirect URL for '{}'".format(input_url))
    Log.Debug(u'* URL Redirect: Headers = {}'.format(r.headers))
    return input_url
