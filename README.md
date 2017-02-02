[![Build Status](https://travis-ci.org/eduvpn/vpn-api-client.svg)](https://travis-ci.org/eduvpn/vpn-api-client)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eduvpn/vpn-api-client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eduvpn/vpn-api-client/?branch=master)

VPN API Client.

This application allow users to download VPN configurations using the API 
provided by VPN services in the "federated" model.

It will list all configured VPN instances, obtain an access token at a central
token service and use that token to interact with the VPN instances.
