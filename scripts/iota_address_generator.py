# coding=utf-8
"""
Generates a shiny new IOTA address that you can use for transfers!
"""

from __future__ import absolute_import, division, print_function, \
  unicode_literals

from argparse import ArgumentParser
from getpass import getpass as secure_input
from sys import argv
from typing import Optional, Text

from iota import __version__, Iota
from iota.crypto.types import Seed
from six import binary_type, moves as compat, text_type
import sys


from iota import Iota, ProposedTransaction, Address, TryteString, Tag, Transaction
from iota.crypto.addresses import AddressGenerator
from iota.commands.extended.utils import get_bundles_from_transaction_hashes
import iota.commands.extended.get_latest_inclusion
from iota.json import JsonEncoder

import getpass
import hashlib
import json
import time
import datetime
from operator import itemgetter

from iota import Iota, ProposedTransaction, Address, TryteString, Tag, Transaction
from iota.crypto.addresses import AddressGenerator
from iota.commands.extended.utils import get_bundles_from_transaction_hashes
import iota.commands.extended.get_latest_inclusion
from iota.json import JsonEncoder



# Takes a address (81 Characters) and converts it to an address with checksum (90 Characters)
def address_checksum(address):
    bytes_address = bytes(address)
    addy = Address(bytes_address)
    address = str(addy.with_valid_checksum())
    return address

#bitfinex uses cloudflare which blocks many requests; blocking may get triggered at address pregeneration
#uri = "http://iota.bitfinex.com/"
uri = "http://node01.iotatoken.nl:14265/"
index = int(sys.argv[2])
count = None
seed =  sys.argv[1]
seed = seed.encode('ascii')
api = Iota(uri, seed)
api_response = api.get_new_addresses(index, count)
for addy in api_response['addresses']:
    print(address_checksum(binary_type(addy).decode('ascii')))
    #print(binary_type(addy).decode('ascii'))
