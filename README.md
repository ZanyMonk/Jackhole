# Jackh*le
## A one-file HTTP bin written in PHP
This script uses it's own file to store HTTP requests it receives. The requests can then be analysed by using a key generated randomly the first time the script gets executed. This key is stored in a cookie.

## Installation
1) Drop the script somewhere in the webserver's document root
2) Access it once to initialize the script

A random password is generated on first access. It gives access to the
admin page. However, nothing is encrypted ! All data is appended at the
end of the script file, base64 encoded.

Despite it's self-writing/reading behavior, this script is packable
(ie. eval(base64_decode("..."))) but `__FILE__` superglobal has to be
parsed manually because it's different from the value returned in
`eval()`'d context.
```php
eval(str_replace('__FILE__', "'".__FILE__."'", "..."));
```

## To do
- `Share` button
- Settings
- Custom redirection
- Handle $_FILES
- Packer
