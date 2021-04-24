import requests
import time as t
import sys

url = "http://localhost/pi_api/gpio/?a=readPin&pin=1"


while True:
    try:
        request = requests.request("GET", url).json()
        print(request['data'])
        t.sleep(30)
    except KeyboardInterrupt:
        sys.exit(0)
