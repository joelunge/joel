# import libraries
import urllib2
from bs4 import BeautifulSoup

# specify the url
quote_page = 'https://www.xe.com/currencyconverter/convert/?Amount=1&From=USD&To=SEK'

# query the website and return the html to the variable 'page'
page = urllib2.urlopen(quote_page)

# parse the html using beautiful soup and store in variable `soup`
soup = BeautifulSoup(page, 'html.parser')

# Take out the <span> of price and get its value
name_box = soup.find('span', attrs={'class': 'converterresult-toAmount'})

price = name_box.text.strip() # strip() is used to remove starting and trailing
print price