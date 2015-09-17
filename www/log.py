import os
import glob
import datetime
import urlparse

data = glob.glob('eval_*')
#data = glob.glob('eval_201204*')
count = 0
total = 0
bydate = {}
bytime = {}
domains = {}

for file in data:
	item = open(file)
	line = item.readlines()

	for row in line:
		items = row.split("\t")
		if len(items) < 5:
			print file

		total += 1
		timestamp = items[0]
		ip = items[1]
		uas = items[2]
		url = items[3]
		res = items[4].strip()

		if res == 'error':
			count += 1
			continue

		itemdate = datetime.date.fromtimestamp(float(timestamp))
		if bydate.has_key(itemdate):
			bydate[itemdate] += 1
		else:
			bydate[itemdate] = 1

		itemtime = datetime.datetime.fromtimestamp(float(timestamp)).hour
		if bytime.has_key(itemtime):
			bytime[itemtime] += 1
		else:
			bytime[itemtime] = 1

		domain = urlparse.urlparse(url).netloc
		if domains.has_key(domain):
			domains[domain] += 1
		else:
			domains[domain] = 1


for key, value in sorted(domains.iteritems()):
	if value > 500:
		print '%s %d' % (key, value)

for key, value in sorted(bydate.iteritems()):
	print '%s %d' % (key, value)

for key, value in sorted(bytime.iteritems()):
	print '%s %d' % (key, value)

print total
