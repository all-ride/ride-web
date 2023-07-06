# ride-web
## 1.1.5 - 2023-07-06
### Updated
- avoid a file injection vulnerability we only return file contents of file paths that start with the public dir and application/public
## 1.1.4 - 2019-11-06
### Updated
- use Throwable for ExceptionView instead of Exception

## 1.1.3 - 2019-06-19
### Updated
- remove session file for an empty session

## 1.1.2 - 2017-08-16
### Added
- added CGIPassAuth in comment to .htaccess, needed when using HTTP authentication
### Updated 
- catch exception when routing invalid requests

## 1.1.1 - 2017-06-01
### Updated
- pass $isSecure to HttpFactory when creating a new request

## 1.1.0 - 2017-05-02
### Updated
- implemented "base" for file includes in routes.json

## 1.0.2 - 2017-04-19
### Updated
- cache route permissions

## [1.0.1] - 2017-02-10
### Added 
- log message when the view is rendered
### Updated
- FileController catches invalid file requests like %5C

## [1.0.0] - 2016-10-14
### Updated 
- README.md
- composer.json for 1.0
