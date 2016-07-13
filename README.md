# textPenses
a PHP app that tracks household expenses, using SMS for updating

This application uses Nexmo to update and report on a database
in order to track shared household expenses. 

Nexmo uses SMS to send data to this page via a GET request.
The Nexmo number is removed, and the callback to this page
is hard-coded in the settings on my Nexmo dashboard: 
https://dashboard.nexmo.com/

"direct" is a keyword that signals that the transaction is a 
direct payment, person to person rather than a paid-for shared
expense. Direct payments are calculated differently (2x).

"total" is a keyword that sends an email to the address associated
with that phone number, with the total owed

$ is a key that signals the amount of the transaction follows.
Do not use a $ or "direct" or "total" anywhere else in the text message.

When an expense is successfully added, user gets an email confirmation.

Planned features:
- validate submission for more than one $, no amount, no $ but readable amount
- 
New This version:
- fixed bug with other phone numbers
- made database display editable, so you can delete or change any entry
