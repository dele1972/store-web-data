# store-web-data

store captured data to my db

## ToDo's

- scan folder --> OK
  - set config as 'global' (class)
  - if update date not in DB
    - ... store data in DB 
  - everytime - archive both files
    - delete original files
- update
  - several times the base html is changed in the structure or by adding more information
    - [ ] 2020-10-02 start 7 day incidence (in 1st paragraph)
      - [x] implemented (but not updated)
    - [ ] 2020-10-19 start 7 day incidence (as another col)
    - [ ] 2020-10-28 till this filedate the above updates has to be updated
    - [ ] 2021-01-04 new 1st paragraph detected
    - [ ] 2021-01-08 strange 1st paragraph detected
    - [ ] 2021-01-12 tables are presented in another order
- logging?
- classes
- simplify
- tests

## information

- rename/move needs 764 permission on samples, processed, error, duplicate
- config/AppConfig.php should only be used as a template
  - copy that to another folder (outside of document root)
  - include that 
- max. people count of region hanover: 1.178.781 ([07/2019](https://www.hannover.de/Leben-in-der-Region-Hannover/Verwaltungen-Kommunen/Die-Verwaltung-der-Region-Hannover/Region-Hannover/Weitere-Meldungen-der-Region-Hannover/2019/Neue-Bev%C3%B6lkerungszahlen))

## By using XAMPP for local development

### Download XAMPP

https://www.apachefriends.org/de/download.html

### Installation

1. Change the permissions to the installer

```shell
chmod 755 xampp-linux-*-installer.run
```

2. Run the installer

```shell
sudo ./xampp-linux-*-installer.run
```

### Work with XAMPP

#### start

```shell
sudo /opt/lampp/lampp start
```

#### stop

```shell
sudo /opt/lampp/lampp stop
```


#### start GUI

```shell
cd /opt/lampp
sudo ./manager-linux.run (or manager-linux-x64.run)
```


### XAMPP configuration files

- **Apache**: `/opt/lampp/etc/httpd.conf`, `/opt/lampp/etc/extra/httpd-xampp.conf`
- **PHP**: `/opt/lampp/etc/php.ini`
- **MySQL**: `/opt/lampp/etc/my.cnf`
- **ProFTPD**: `/opt/lampp/etc/proftpd.conf`


### Work with it

To open the Project with `http://localhost/dev/`

#### Set a symbolic link to the htdocs folder (`/opt/lampp/htdocs/dev`)

```shell
sudo ln -s -r /home/$USER/dev/web/domains/$USER.de/htdocs/store-web-data /opt/lampp/htdocs/dev
```

#### Remove the symbolic link from the htdocs folder (`/opt/lampp/htdocs/dev`)

```shell
sudo unlink /opt/lampp/htdocs/dev
```
