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

$ is a key that signals the amount of the transaction follows.
Do not use a $ or "direct" anywhere else in the text message.

Planned features:
- validate submission for more than one $, no amount, no $ but readable amount
- send an email with either a confirmation or error messages after submission
- make it fun by putting a random gif in the confirmation?
- balance report - make email work

fixed bug with other phone numbers
started building balance request
