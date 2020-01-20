#! /usr/bin/env python3
import requests

base_url = "https://mautic.k8s.facetinteractive.com/api"
username = "jordan"
passwd = "XXXXX"


def get_contact_data(contact_id: int):
    url = f"{base_url}/contacts/" + str(contact_id)
    r = requests.get(url, auth=(username, passwd))
    return r.json()

def get_contact_by_email(email):
    url = f"{base_url}/contacts"
    r = requests.get(url, auth=(username, passwd), params={"search":email})
    if r.status_code == 200:
        emails = r.json()["emails"]
        return int(list(emails)[0])

def create_contact(contact):
    url = f"{base_url}/contacts/new"
    r = requests.post(url, auth=(username, passwd), data=contact)
    return r.json()

def get_email_id(slug):
    url = f"{base_url}/emails"
    r = requests.get(url, auth=(username, passwd), params={"search":slug})
    if r.status_code == 200:
        emails = r.json()["emails"]
        return int(list(emails)[0])

def send_email_to_contact(contact_id, email_id, tokens):
    url = f"{base_url}/emails/{email_id}/contact/{contact_id}/send"
    r = requests.post(url, auth=(username, passwd), data=tokens)
    return r

def get_segments():
    url = f"{base_url}/segments"
    r = requests.get(url, auth=(username, passwd), params={"limit": 100})
    return r.json()

"""
>>> list(r["lists"])
['20', '21', '31', '47', '12', '64', '51', '43', '59', '14', '52', '53', '46', '37', '36', '16', '13', '15', '18', '66', '11', '28', '34', '40', '32', '33', '8', '29', '27', '30', '54', '55', '56', '57', '65', '63', '62', '61', '10', '26', '17', '22', '24', '25', '19', '60', '38', '39', '23', '67', '35', '41', '42', '58', '48', '49', '50', '44', '45']
>>> r["lists"]['67']
{'isPublished': True, 'dateAdded': '2020-01-20T15:57:52+00:00', 'dateModified': None, 'createdBy': 1, 'createdByUser': 'Jordan Ryan', 'modifiedBy': None, 'modifiedByUser': None, 'id': 67, 'name': 'Test Segment', 'alias': 'test-segment', 'description': None, 'filters': [], 'isGlobal': True, 'isPreferenceCenter': False}
"""


def add_contact_to_segment(segment_id, contact_id):
    url = f"{base_url}/segments/{segment_id}/contact/{contact_id}/add"
    r = requests.post(url, auth=(username, passwd))
    return r
    

get_contact_data(79814)


contact = {
    "firstname": "Lakshmi",
    "lastname": "P",
    "email":"lakshmi.narasimhan@axelerant.com",
    "company":"Axl",
    "phone": "+91 875 447 8359",
    "city": "Chennai",
    "state": "Tamil Nadu",
    "zipcode": "600061",
    "country": "India",
    "twitter": "lakshminp",
}
    

create_contact(contact)

get_email_id("bv-100")

data = {"tokens" : {"lead": { "customer": { "first_name": "FOOBAR"}}}}
send_email_to_contact(80592, 194, data)

add_contact_to_segment(67, 80592)
