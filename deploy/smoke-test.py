"""Run against the local Docker demo; creates and deletes one test project."""
import http.cookiejar
import json
import re
import urllib.parse
import urllib.request
from pathlib import Path

root = Path(__file__).resolve().parent.parent
base = 'http://localhost:8088'
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def get(path):
    return client.open(base + path, timeout=15).read().decode()

def token(html):
    return re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)

def post(path, values):
    return client.open(base + path, urllib.parse.urlencode(values).encode(), timeout=15).read().decode()

assert json.loads(get('/health'))['status'] == 'ok'
print('PASS database health')
login = get('/login')
page = post('/login', {'csrf': token(login), 'username': 'admin', 'password': (root/'secrets/admin_password').read_text().strip()})
assert 'Content Studio'.lower() in page.lower()
print('PASS admin login')
csrf = token(page)
title = 'Docker smoke test temporary project'
page = post('/admin/project', {'csrf': csrf, 'title': title, 'description': 'Temporary integration check', 'tech': 'Docker', 'url': 'https://example.com', 'published': 'on'})
assert title in get('/')
# Match the form containing this exact title, avoiding another project's id.
forms = re.findall(r'<form method="post" action="/admin/project">(.*?)</form>', page, re.S)
project_id = next(re.search(r'name="id" value="(\d+)"', form).group(1) for form in forms if 'value="'+title+'"' in form)
print('PASS create and publish project')
page = post('/admin/project', {'csrf': csrf, 'id': project_id, 'title': title, 'description': 'Updated hidden project', 'tech': 'Docker', 'url': ''})
assert title not in get('/') and 'Updated hidden project' in page
print('PASS update and hide project')
post('/admin/delete', {'csrf': csrf, 'id': project_id})
assert title not in get('/admin')
print('PASS delete project')
post('/logout', {'csrf': csrf})
assert 'autocomplete="current-password"' in get('/admin')
print('PASS logout and protected admin')
response = client.open(base)
for key in ['Content-Security-Policy', 'X-Frame-Options', 'X-Content-Type-Options']:
    assert response.headers.get(key), key
print('PASS Nginx security headers')
for port, path in [(8089, '/'), (3008, '/api/health'), (9098, '/-/ready')]:
    assert urllib.request.urlopen(f'http://127.0.0.1:{port}{path}', timeout=20).status == 200
print('PASS phpMyAdmin, Grafana, Prometheus HTTP readiness')
