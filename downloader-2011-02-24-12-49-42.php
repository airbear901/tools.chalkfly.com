<?php
class Magento_Downloader_Validator
{
    /**
     * Minimum required PHP version.
     *
     * @var string
     */
    protected $_phpVersion = '5.2.0';

    /**
     * Required php extensions.
     *
     * @var array
     */
    protected $_phpExtensions = array(
        'curl',
        'dom',
        'gd',
        'hash',
        'iconv',
        'mcrypt',
        'pcre',
        'pdo',
        'pdo_mysql',
        'simplexml'
    );

    /**
     * Minimum required MySQL version.
     *
     * @var string
     */
    protected $_mysqlVersion = '4.1.2';

    /**
     * Collection for errors.
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Collection for messages.
     *
     * @var array
     */
    protected $_messages = array();

    /**
     * Resuource link.
     *
     * @var resource
     */
    protected $_connection = null;

    /**
     * Retrieve errors from collector.
     *
     * @return array
     */
    public function getErrors()
    {
        $errors = $this->_errors;
        $this->_errors = array();
        return $errors;
    }

    /**
     * Put error to error's collection.
     *
     * @param string $text
     * @param int|bool $position
     * @return Magento_Downloader_Validator
     */
    public function addError($text, $position=false)
    {
        if ($position === false) {
            $this->_errors[] = $text;
        } else {
            $this->_errors[$position] = $text;
        }
        return $this;
    }

    /**
     * Retrieve message from message's collection.
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = $this->_messages;
        unset($this->_messages);
        return $messages;
    }

    /**
     * Put message to message's collection.
     *
     * @param string $text
     * @param integer|boolean $position
     * @return Magento_Downloader_Validator
     */
    public function addMessage($text, $position=false)
    {
        if ($position === false) {
            $this->_messages[] = $text;
        } else {
            $this->_messages[$position] = $text;
        }
        return $this;
    }

    /**
     * Check PHP for Magento.
     *
     * @return Magento_Downloader_Validator
     */
    public function validatePhp()
    {
        $this->_checkPhpVersion()
             ->_checkPhpExtension();
        return $this;
    }

    /**
     * Check PHP version on current server.
     *
     * @return Magento_Downloader_Validator
     */
    protected function _checkPhpVersion()
    {
        $version = phpversion();
        if (version_compare($version, '5.2.0', '<')===true) {
            $this->addError('Whoops, it looks like you have an invalid PHP version. Magento supports PHP 5.2.0 or newer.');
        } else {
            $this->addMessage('PHP version is right. Your version is ' . $version . '.');
        }
        return $this;
    }

    /**
     * Check available PHP extensions on current server.
     *
     * @return Magento_Downloader_Validator
     */
    protected function _checkPhpExtension()
    {
        foreach ($this->_phpExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $this->addError('PHP Extension ' . $extension . ' must be loaded');
            } else {
                $this->addMessage('PHP Extension ' . $extension . ' is loaded');
            }
        }
        return $this;
    }

    /**
     * Check database needed for Magento.
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @return Magento_Downloader_Validator
     */
    public function validateDb($host = 'localhost', $username = '', $password = '')
    {
        $this->_setConnection($host, $username, $password)
             ->_checkDbVersion()
             ->_checkDbInnoDb();
        return $this;
    }

    /**
     * Create connection with database that user has defined.
     *
     * @param mixed $host
     * @param mixed $username
     * @param mixed $password
     * @return Magento_Downloader_Validator
     */
    protected function _setConnection($host = 'localhost', $username = '', $password = '')
    {
        try {
            $dsn = 'mysql:host=' . $host . ';';
            $this->_connection = new PDO($dsn, $username, $password);
        } catch (PDOException $e) {
            $this->addError('Access denied for user ' . $username . '@' . $host);
        }
        return $this;
    }

    /**
     * Check database version needed for Magento.
     *
     * @return Magento_Downloader_Validator
     */
    protected function _checkDbVersion()
    {
        if (!$this->_connection) {
            return $this;
        }
        $result = $this->_connection->query('show variables like \'version\';');
        $version = $result->fetchColumn(1);
        $match = array();
        if (preg_match("#^([0-9\.]+)#", $version, $match)) {
            $version = $match[0];
        }
        if (version_compare($version, $this->_mysqlVersion) == -1) {
            $this->addError('Database server version does not match system requirements (required: '
                . $this->_mysqlVersion
                . ', actual: '
                . $version
                .')');
        } else {
            $this->addMessage('Database server version matches system requirements (required: '
                . $this->_mysqlVersion
                . ', actual: '
                . $version
                .')');
        }
        return $this;
    }

    /**
     * Check availabe InnoDB on database.
     *
     * @return Magento_Downloader_Validator
     */
    protected function _checkDbInnoDb()
    {
        if (!$this->_connection) {
            return $this;
        }
        $result = $this->_connection->query('show variables like \'have_innodb\';');
        $innoDb = $result->fetchColumn(1);
        if ($innoDb != 'YES') {
            $this->addError('Database server does not support InnoDB storage engine');
        } else {
            $this->addMessage('Database server supports InnoDB storage engine');
        }
        return $this;
    }

    /**
     * Check current folder permission.
     *
     * @return Magento_Downloader_Validator
     */
    public function validatePermissions()
    {
        $rootPath = dirname(__FILE__);
        $rootPath = realpath($rootPath);
        if (!is_readable($rootPath)) {
            $this->addError('Path ' . $rootPath . ' must be readable.');
        }
        if (!is_writeable($rootPath)) {
            $this->addError('Path ' . $rootPath . ' must be writable.');
        }
        return $this;
    }
}

class Magento_Downloader_Worker
{
    /**
     * URL of latest version of Magento.
     *
     * @var string
     */
    const MAGENTO_STORAGE = 'https://connect20.magentocommerce.com/enterprise/magento-downloader-latest.tar.gz';

    const MCM_REMOTE_FTP_HOST = 'connect20.magentocommerce.com';
    const MCM_REMOTE_FTP_FILE = 'enterprise/magento-downloader-latest.tar.gz';

    /**
     * Authorization URL
     *
     * @var string
     */
    const AUTHORIZE_URL = 'https://connect20.magentocommerce.com/login/';

    /**
     * Destination file.
     *
     * @var string
     */
    const DESTINATION_FILE = 'downloader.tar.gz';

    /**
     * Development mode flag
     *
     * @var bool
     */
    const DEVELOPMENT_MODE = false;

    /**
     * Session array
     *
     * @var array
     */
    protected $_session;

    /**
     * Init session and validator model
     */
    public function __construct()
    {
        if (!isset($_SESSION)) {
            session_name('magento_downloader_session');
            session_start();
        }
        $this->_session   = &$_SESSION;
        $this->_validator = new Magento_Downloader_Validator();
    }

    /**
     * Check whether current folder is writable
     *
     * @return bool
     */
    public function isCurrentFolderWritable()
    {
        return is_writeable(realpath(dirname(__FILE__)));
    }

    /**
     * Retrieve path of destination file
     *
     * @return string
     */
    public function getDestinationFilePath()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::DESTINATION_FILE;
    }

    /**
     * Authorize user by specified credentials
     *
     * @param string $login
     * @param string $password
     * @return bool|string
     */
    public function authorize($login, $password)
    {
        $ch = curl_init(self::AUTHORIZE_URL);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'login'     => $login,
            'password'  => $password,
            'channel'   => 'enterprise'
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            return $error;
        }
        return ($result == 'Success');
    }

    /**
     * Authorize user by specified credentials
     *
     * @param string $login
     * @param string $password
     * @return bool|string
    */
    public function ftpAuthorize($login, $password)
    {
        $ftpHost = self::MCM_REMOTE_FTP_HOST;

        $connId = @ftp_connect($ftpHost);

        if ($connId) {
            if (!@ftp_login($connId, $login, $password)) {
                return false;
            }
            @ftp_pasv($connId, true);
            if (!@ftp_nlist($connId, ".")) {
                return false;
            }
            ftp_close($connId);
        } else {
            return false;
        }
        return true;
    }

    /**
     * Download last version of magento from magentocommerce.com.
     *
     * @throws Exception
     * @return Magento_Downloader_Worker
     */
    public function download()
    {
        if(self::DEVELOPMENT_MODE) {
            if(file_exists($this->getDestinationFilePath())) {
                return $this;
            }
        }
        $login = $this->_session['auth']['username'];
        $pswd = $this->_session['auth']['password'];

        $auth = $this->authorize($login, $pswd);
        if (false === $auth) {
            throw new Exception("Failed to authorize as {$login}");
        } elseif (is_string($auth)) {
            throw new Exception('Could not connect to server');
        }
        $fp = fopen($this->getDestinationFilePath(), 'wb');
        if (!$fp) {
            throw new Exception('Can\'t open file ' . $this->getDestinationFilePath());
        }
        $ch = curl_init(self::MAGENTO_STORAGE);
        if (isset($this->_session['auth'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                'login'     => $login,
                'password'  => $pswd
            ));
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if ($error) {
            throw new Exception($error);
        }
        return $this;
    }

    /**
     * Download MCM via FTP
     *
     * @return Magento_Downloader_Worker
     */
    public function ftpDownload()
    {
        $ftpHost = self::MCM_REMOTE_FTP_HOST;
        $ftpUser = $this->_session['auth']['username'];
        $ftpPass = $this->_session['auth']['password'];

        $connId = @ftp_connect($ftpHost);

        if ($connId) {
            if (!@ftp_login($connId, $ftpUser, $ftpPass)) {
                throw new Exception("Could not connect as $ftpUser on $ftpHost\\n");
            }
            @ftp_pasv($connId, true);
            $fp = fopen($this->getDestinationFilePath(), 'wb');
            if (!$fp) {
                throw new Exception('Can\'t open file ' . $this->getDestinationFilePath());
            }
            @ftp_pasv($connId, true);
            if (!@ftp_fget($connId, $fp, self::MCM_REMOTE_FTP_FILE, FTP_BINARY, 0)) {
                throw new Exception("Could not download MCM from $ftpHost");
            }
            ftp_close($connId);
            fclose($fp);
        } else {
            throw new Exception("Could not connect to $ftpHost");
        }
        return $this;
    }

    /**
     * Extract content of archive to ./
     * @var bool $forceTmp
     * @throws Exception
     * @return Magento_Downloader_Worker
     */
    public function unpack($forceTmp=false)
    {
        $source = $this->getDestinationFilePath();
        $gzPointer = gzopen($source, 'r' );
        if (empty($gzPointer)) {
            throw new Exception('Can\'t open GZ archive ' . $source);
        }
        $data = '';
        while (!gzeof($gzPointer)) {
            $data .= gzread($gzPointer, 131072);
        }
        gzclose($gzPointer);
        unlink($source);
        $source = str_replace('.gz', '', $source);
        file_put_contents($source, $data);
        $pointer = fopen($source, 'r');
        if (empty($pointer)) {
            throw new Exception('Can\'t open TAR archive ' . $source);
        }

        $targetPath = '';
        if (!$this->isCurrentFolderWritable()||$forceTmp) {
            $targetPath = realpath(dirname($this->getDestinationFilePath()))
                . DIRECTORY_SEPARATOR . 'magento' . DIRECTORY_SEPARATOR;
            @mkdir(realpath(dirname($this->getDestinationFilePath())) . DIRECTORY_SEPARATOR . 'magento', 0777, true);
        }

        while (!feof($pointer)) {
            $header = $this->_parseTarHeader($pointer);
            if ($header !== false) {
                $currentFile = $header['name'];
                if ($header['type']=='5') {
                    @mkdir($targetPath . $currentFile, 0777, true);
                } elseif (($header['type']=='' || $header['type']=='0' || $header['type']==chr(0))) {
                    file_put_contents($targetPath . $currentFile, $header['data']);
                }
            }
        }
        fclose($pointer);
        unlink($source);
        return $this;
    }

    /**
     * Copy extracted archieve
     *
     * @param array $credentials
     */
    public function ftpCopy(array $credentials)
    {
        $ftpHost = $credentials['ftp_host'];
        $ftpUser = $credentials['ftp_username'];
        $ftpPass = $credentials['ftp_password'];
        $ftpPath = isset($credentials['ftp_path']) ? $credentials['ftp_path'] : '/';

        $connId = @ftp_connect($ftpHost);

        if ($connId) {
            if (!@ftp_login($connId, $ftpUser, $ftpPass)) {
                throw new Exception("Could not connect as $ftpUser on $ftpHost\\n");
            }
            @ftp_pasv($connId, true);
            $tmpDir = realpath(dirname($this->getDestinationFilePath())) . DIRECTORY_SEPARATOR . 'magento';
            $this->_ftpCopyRecursive($connId, $tmpDir, $ftpPath);
            ftp_close($connId);
            $this->rmdirRecursive($tmpDir);
        } else {
            throw new Exception("Could not connect to $ftpHost");
        }
    }

    /**
     * Recursive copy specified directory to specified directory on ftp host
     *
     * @param resource $connId
     * @param string $srcDir
     * @param string $dstDir
     */
    protected function _ftpCopyRecursive($connId, $srcDir, $dstDir = '/')
    {
        $dir = dir($srcDir);
        while ($file = $dir->read()) {
            if ($file != "." && $file != "..") {
                if (is_dir($srcDir . DIRECTORY_SEPARATOR . $file)) {
                    if (!@ftp_nlist($connId, $dstDir . DIRECTORY_SEPARATOR . $file)) {
                        ftp_mkdir($connId, $dstDir . DIRECTORY_SEPARATOR . $file);
                    }
                    $this->_ftpCopyRecursive($connId,
                        $srcDir . DIRECTORY_SEPARATOR . $file, $dstDir . DIRECTORY_SEPARATOR . $file);
                } else {
                    ftp_put($connId,
                        $dstDir . DIRECTORY_SEPARATOR . $file, $srcDir . DIRECTORY_SEPARATOR . $file, FTP_BINARY);
                }
            }
        }
        $dir->close();
    }

    /**
     * Delete a directory recursively
     *
     * @param string $dir
     * @param bool $recursive
     * @return bool
     */
    public function rmdirRecursive($dir, $recursive = true)
    {
        if ($recursive) {
            if (is_dir($dir)) {
                foreach (scandir($dir) as $item) {
                    if (!strcmp($item, '.') || !strcmp($item, '..')) {
                        continue;
                    }
                    $this->rmdirRecursive($dir . DIRECTORY_SEPARATOR . $item, $recursive);
                }
                $result = @rmdir($dir);
            } else {
                $result = @unlink($dir);
            }
        } else {
            $result = @rmdir($dir);
        }
        return $result;
    }

    /**
     * Get header from TAR string and unpacked it by format.
     *
     * @param resource $pointer
     * @return string|bool
     */
    protected function _parseTarHeader(&$pointer)
    {
        $firstLine = fread($pointer, 512);
        if (strlen($firstLine)<512){
            return false;
        }
        $header = unpack ('a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix/a12closer', $firstLine);
        $header['mode']=$header['mode']+0;
        $header['uid']=octdec($header['uid']);
        $header['gid']=octdec($header['gid']);
        $header['size']=octdec($header['size']);
        $header['mtime']=octdec($header['mtime']);
        $header['checksum']=octdec($header['checksum']);
        $checksum = 0;
        $firstLine = substr_replace($firstLine, '        ', 148, 8);
        for ($i = 0; $i < 512; $i++) {
            $checksum += ord(substr($firstLine, $i, 1));
        }
        if (isset($header['name']) && $header['checksum'] == $checksum) {
            if ($header['name'] == '././@LongLink' && $header['type'] == 'L') {
                $realName = substr(fread($pointer, floor(($header['size'] + 512 - 1) / 512) * 512), 0, $header['size']);
                $headerMain = $this->_parseTarHeader($pointer);
                $headerMain['name'] = $realName;
                return $headerMain;
            } else {
                if ($header['size']>0) {
                    $header['data'] = substr(fread($pointer, floor(($header['size'] + 512 - 1) / 512) * 512), 0, $header['size']);
                } else {
                    $header['data'] = '';
                }
                return $header;
            }
        }
        return false;
    }
}

class Magento_Downloader_Helper
{
    /**
     * List of interface steps for installing Magento.
     *
     * @var array
     */
    protected $_steps = array(
        'welcome'       => 'Welcome',
        'validate'      => 'Validation',
        'deploy'        => 'Magento Connect Manager Deployment',
        'authorize'     => 'Channel Server Authorization',
        'download'      => 'Download',
        'begin'         => 'License Agreement',
        'locale'        => 'Localization',
        'config'        => 'Configuration',
        'administrator' => 'Create Admin Account',
        'end'           => 'You\'re All Set!'
    );

    /**
     * Images.
     *
     * @var array
     */
    protected $_images  = array(
        'error.gif' => array(
            'base64'    => 'R0lGODlhEAAQAPeAAOxwW+psWe5zXPN8YOtuWvu9qednV/B4X+92XfWCY+JfU+hpWPF6X/N+Yfi0oOZlVvaJa+ViVfbZ0vrJvvKpn/Omkfrd1vSAYuWOg9yXiN19b8JKMeWzqPLUzvWwo9RkUsNMM+ySf/aKcvKKcs5dTPSZhPGon+qNe+yLf+OEdfGTgul9aNVfRup1XOmllva0pM1hS+FdUvq5qfCXg+y6r+BzYPrZ0+yYifTDuOa0qfjb1Pq8qOlvX+NmW+NhVOx/Z/GdkPm5puVxWOeRhfiiidFhUPPVzvWDafGlmfSMdORnXN1uVsxfSfHTzO6DbveFa8VONeuJfe2SifSsofGXhOFyWu2fleaIePLBtvmRee6qm9FhScxVO8ZaQ+dsXd1wXfezpMZVPt6Zi/ihiPCfjsNSO/ijiviGbPi1pfmMdOqHffOvpuGdjtBYQOh/Z/KAZe6gld18b/i2ofWBYvSmku16YPGom+yBbNhtVuySiOeQhPi1pu68sfezoPSEZ/////rr5wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAIAALAAAAAAQABAAAAjSAAEJHEiwYEELMrI8OZLkhQ6DgCakcULHgYMKK37gKDjhDJUCZiBAILIjhBAsAy2ImFEgQYI/fxoMCHKiigSBe+60nHMBJoMDCNB8cSFwRIUxF2TCRCAAgIobeATWkeNnwE+YAAgE4GGnjcAWfd4AFWDjT4AFBrwg4SLQDZkSTQkAWWPgQYQoQ2AI1FIDjNYFMCP4UEChiBiBEpZc8VBXSh4FMShoCNNhIB8WKaagUNJDjYk4G3IUpLHlgx44VjCQKMMBohE2TKCA6JKhCcTbBQMCADs=',
            'type'      => 'image/gif'
        ),
        'success.gif' => array(
            'base64'    => 'R0lGODlhEAAQAPeeAJDOf67cpYPOd7HLr53YknLIaPz9+7fhr7XhrnrMbW/CYW23V67Xoa/XoLTaprTZpb/juG7EYqnbl0yXPd3q2jN7MJfMhXO6XK/cpm7EYTR/MW+1WHC/V2vDSnPHZmSwTGnCSLLUsFSyNIXFdbXWsL7jtnnBZHTDZE6bQbXbqNvl2pzOjNrn2ZrUjZ3Oi/3+/VG2LSmPJCaDI5/Skb3esbnasH7BaXG5W7fhsCh5JCZ+IyZyJGbESJnNimq5UHXIaF6pSLPZpVy0PY68i2/GZK3em5bNiGy2VpbHg9bu0nDBY6fYk0iwJ+3364nEdsbnunS3WzSOMUKgMkOgMj6MOrTfrH+4aXC4WePw3pbLhqHWlVzCPKPXlnDHZW61WI24in/KcD6KOnbKavH58HbJaXy6ZHe8YLHdp9rk2Y61i/T68t7r2ovIeLTdqtns1JrHh1KgQnK5W6HXlZrKh37Hb4DMcnHEY3S3XHG+X6vTm6vSm+Px3m+1WbLbqH28ZrXfrOHu23nJayh2JD6YO5vXkX23ZnTCWVzAOsDkuYDKczuhJpjLhnTIaH2+Z1q+N4fJeYO+bZnRi2nHScfnuj6EOpDEi27FY5nQjJjMh3HJVOb044fCcm/DYf////j39wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAJ4ALAAAAAAQABAAAAjdAD0JHEiwYME9RTIdcgRiCSCDniZJMsQlQAAtPkTQKPhkC4ADBAQIIICAjaIaA7HwAIAggZgCjH4EquJECgWBEjgcSFCgi6UIdkrQ+QOkksAOcgiRIZKBkwJEnTRFcjFIIIwzdTzgSKIEQicDl2wwkCGQCQYwicZ0avLVyI1GDXQIFDKjxYlHajq9yLIgjoU3YQSu+NDGBJ4Rbnoc8XLHAYovAtdM2dTHzIUrfDZACWJFg4qBJGJASoHJT5lFDwrtGFAwRBQ4c/LoQTKhAmuDLIZQySGIUho0EIMXDAgAOw==',
            'type'      => 'image/gif'
        ),
        'note.gif' => array(
            'base64'    => 'R0lGODlhEAAQAPefAP787/bhdPbhc/bgcv/++ffnv9eBLPHSlP765fPTpvLSk+7GfqOVev342+rq6/PUpf351OyjTPjly/7520ZGRfz2zf32z/jjtP320v334eq5ae27a/777Pbgcffv1+Wqb/vv0v351vz0yf3lQf354NzTvvft1OzAfN58FuaoT+qZL/vDUe+xYNiJMuuhOOiyZ/z0x++1aPTkx/e+S/CuZfXcWfj14lhELvLKkPbkau+0Y/fv2szMzf788PTesfjw2P3230JCQJiKOlBNRVtXTurAgru8vO/KhJiZnfzz1/HOrO/DnN2VPvPbruWxdPvxyouGd/744vbdpu7Fff/97ffiy/776/787uymTPjkt/z1yPvxuPz00/763Oqza+Gvbp+ho/DIfvzw2P754PvxtP3mR2BZUOiRFvfhd+adRvzwxPvxqPjlhv31wPjqlouPlPLdvPnpyfXiXffjfPbhdfXhsWxlV6KlpvDMhdeDLP331v766/fkgvr56ffmv/vyuv353/blu/7+3HZuYNqOOdOIPO/Ii/XeaPflcvjx1uPcyJeDZpSNf/331frrw/zyz/787P354uaGCfPVRunizv304v765Pr56uvk0frrivjftvvsx6KiqPr35ox/Nfr67AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAJ8ALAAAAAAQABAAAAjbAD8J/JQokJQDeMIc8TFwYCc/WTYlyRBFjKYNGn4M7FPHUSQSjSyIgBFiD44UNgQWuICggaUeBAgAwECGipciAhWAmACIwxUAAKyM2bJGAhOBC4BoqaCHy6Mnav64QVSphcApcSDMYcMihg4sfGpkqkJIYBNDkNCooBPgkABJkwR9cCLw0okHXVwEyCFnwJk2SvLsGOjhRQIaAQQM6BBhiQE4DT+Z+JJmRpkRK1AUkhFZIKVFDIR4GnQDU+eBUO5wQvLGzOmBjIw44AHGzmuBiohQCDKkxO3fAgMCADs=',
            'type'      => 'image/gif'
        ),
        'logo.gif' => array(
            'base64'    => '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',
            'type'      => 'image/gif'
        ),
        'bkg_header.jpg' => array(
            'base64'    => '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444444444444444444444444444444444444444/3SfL8g1/1+N0+X5Br/r984444444444444445IcckSHJEhxxxxxxxxxx/gdPl+Qa/wCvxGwwwyDIRQihFKEEoQShDGhDGhrxoa8aGvGiGrCiGrCiGrD6UNOH0oacPpQT+H/LBP8AH+iGvGhrxoa8aIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaIasaJ8BePiFhhhhhlGUZRlIqRWxFbEcrdkcrdkMrdkMrdkMrdkM7dkM7dkM7dmvO3Zrzt2J/hnH/wCdkcrdkcrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrdkMrd/AXj43T5fkGvy/EmGGGGGGGGGGGGGGGGG+xhhhhhhhhhhhhhhhhhhhhv31xPl+w/486DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoOg6DoL/ADRh0HQdB0PT4D9T1PU9T1PU9T1PU9T1PU9T1PU9T1PU9T1PX7fU9f3fx+Qa/L8Nf9px/unHH/bcccccccccccccccccccccccccccf7HHH+1x/tf7pPl8BMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMML/JIkUoRShBKEMaEMaEMaEMaEMaGvGhrxoa8aGvGhrxoQxoQxoQxoQShBKEEoRQihFCK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JJJJJJJJJJJJJJJJJJJJJJJJJLCTADJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJL/8A/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A7cCgst4ftfb9bfeAAyViSAFyWj5tyWECSSSSSSSSSSSSSSSSSSSSSSSSSSSSwkwAySSSSSSSSSSSSSSSSSSSSSSSSSSSSSStttttttttttttttttttttttttttttttJWMDLXhG2Xf/tpgkCSwyTWeAS2GkkgGSSSSSSSSSSSSSSSSSSAdzgI3jxIUsrCXckkkkkkkkkkkkkkkkkkkkkkkkkkkkkkbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbefDoLN4miSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSS8De7yWSfUXcGnCqxW43ROW3bbbbbbbbbbbbbbbbbaSSSSSSSSSSSSbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbwJift+QKSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSS9pFwcEEUslspMglvPDEr0oltttttttttttttttttAAAAAAAAAAAAASSSSSSSSSSSSSSSSSSSSSSSSSSSSSSWkmmKX/tttrSxtttKTbY2ySUkgBHaZJJf/2SQq2bbb77f9IEl7f2H3JKgB/QQEyZmAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA222222222222222222222222222222TzYalD5HAkjeAAEVtv2z2SwCQgCkn/wSZJr/EzU222222222Wwmw/8ARC/tIHgkcGNttttttttttttttttttttttttttttttm222222222222222222222222222223Ds99+AjX/wDrdtttJL/a2SE3klbTAl/+20k9tJbWySSS2222gSSjZ9n3/pBPu7FCHpJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJtttttttttttttttt/wD/AP8A/wD/AP8A/wD/AP8A/wD6xqc83EXxWWyW2X5GyWSW2SSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSwkwAySSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSWW22222222222222ZMLzyMY2WkE6yyomSS5Jb3lpJPfkQEiSSSSSSSSSSSSSSSSSSSSSSSSSSSSwkwAySSSSSSSSSSSSSSSSSSSSSSSSSSSSSStttttttttttttttttttttttttttttttJWMDLXhG2Xf/tpgkCSwyTWeAS2GkkgGSSSSSSSSSSSSSSSSSSGGC5WrqNQTFehEoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wD/AP8A/wD+oS+6f4f43+2//bSSTaTf+/2kgBAJIAgkkkkkkkkkku22+/8A/tJNLWKsHNqugk5voW22222222222222221pJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJawJ2LE5CpJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJL2kXBwQRSyWykyCW88MSvSyG2222222222222222kAAAAAAAAAAAABJJJJJJJJJJJJJJJJJJJJJJJJJJJJJJaSbQJJJJJJJJJJJJJJJJJJJJJJJaafqacv/v/ALy6S23ZSREkIUrbpAOX4xGxS0nA2222222222222222222222222222222//9oACAEBAwE/EP8Ae/E5HwPdPoTmQzkofwSh+8DjGCJjGBxjBEw/eC5AmMEYAuQcD3ghHs6ntsLbqQ/29S36kd+Pg1iO9QR3KDA6kbkdgbkQbkEcP6W540R3ONxj8x4seKHjRHaW5C4+geEbkfB0BHZm54puex9Xc3REg2gwilvshns1YkUd9jVd1I4dFzIqb7I4c0WZFNfZEVFqVR+kKqupHDcdGJSH6RwOr3CIq3Ujtx+lOfpTn6QmKDmRUf2Q+KHme06O5FH/AGRwa/mRDEVfMjh0/Mrf9IVT9Smf0r/9IjJRD+5oGUN9j0WKCFUP6Uh+yof0smYor37KN/Sjf0oH9Ipz7Kc/SW1nUp39OFW9ThcOQKXcfQSFNU/ZLKHr6FUpqepIOnjU/Mmt/smn/snF68SVHuV4k+3Q5lEPsot9k1Z9k0Z9kyTNJzJpT7EveoxKVfZRr7Mi1e5S77J4x0Ik7g/p7k8ewmYh+KPeYsN2dpEvvLy2kS+jlW7Ct2FbsK3YVuwrdhW7Ct2FbsK3YVuwrdhW7Ct2FbsK3YVuwrdhW7Ct2FbsK3YVuwrdhW7Ct2FbsK3YVuwrdhW7Ct2FbsK3YVuwrdhW7Ct2FbsK3YVuwrdhW7Ct2FbsK3YVuwrdhW7Ct2FbsK3YVuwrdhW7Ct2FbsK3YVuwrdhW7P8AfYHuPkITjngnGJOMSUP3ImIfETEcYjgcEBc0LmkjPnSdjh7J2L+k7F/7Ihn30kqROxSidi7pJGNpJlOU7FWJERHD0nYs+kkN9vSdiw6TsN+HSdiIPeHSdi26bC06TsWnTYRgumws+mwsuk7EQz7ek7Fj02EYOixGBosRh6LCfhRYjDUWLToFlRYjsAeQPws6LEYeixb0WIwVNiJvh6LljQFZijDCtxewWjCG9vgn03CMMSyF7XCCw/gFGjSG+tYsR38EixckeiRPgep8sWPUCFAx/gh48ePHjxo+OXw/S9Gca9CUuhZl9CBJKqgk40TJiTsxXfElkKStOFFYiQ9prYU4cU4ORSUsUhM3wBZ02G/AGCUWMO40exOMEwvFfQI9tOk7GBdJ2MG6TsRHG2dinE7FOJ2KcTsU4nYpxOxTidinE7FOJ2KcTsU4nYpxOxTidinE7FOJ2KcTsU4nYpxOxTidinE7FOJ2KcTsU4nYpxOxTidinE7FOJ2KcTsU4nYpxOxTidinE7FOJ2KcTsU4nYpxOxTidinE7FOJ2KcTsU4nYpxOxTidinE7FOJ2KcTsU4nYpxOxTidinE7FOJ2KcTsU4nYpxOxTidinE7FOJ2KcTsU4nYpxOxTidinE7f77D0fA9hrn2Pge0n29WhjtI7SO0jtI7SO0jtI7SO0nU4HtcXAXAXAXAXAXAXA4Pgy69ji+K6Ht9q6C4a9hcNexVUE1eCioKKgijwcNNiioKKgoZEvMbz7DefYaO/sQKaZ9FT4EfP7lLKWUsofYofYofYzOvYofYiifTJg69jI69il9ji+HXsPgpkPgpkPRsVvsUvsR8eKlit9it9it9it9it9jJ69jJ69il9ih9ih9ih9ih9ih9ih9ih9ih9ih9ih9ih9ih9ih9il9it9it9it9ijwEo2Eo2Jq7Cl9il9hMHXsUeHqtx9xUycjmUslu8rZWyZefqt7v6GLL89itPYbafubFCex7UrUsexEcNRcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcBcP99EREREREREREREREREREREREREREREREREREREREREx9QRn7i+JKUviAoqQIJxUmn4gmEYqXJhFX6TCMdLlCCYx86CR86CjDmcMIOGEERE/AmAmAmGpY1LJZLJZ+yJo9v6m2XD3dylPcs69ylMlSS1XUtV1M9WY/y3EfH3uW/vclT7fe5anUn5onUrTO5XmdytM7laZ3KMzuTjus7kS/Oec7lSZ3J8idyx6zuWPWdyjM7kfJrkpzJXmdytM7laZ3KkzuUJncjypH++qShM7lCZ3KsyVZkrzO5hJzbkeS3KW4UtwrzO5XncV53FedxXncV53ET924pbhS3CluFLcKW4UtwpbhS3CluFadxW3CvuCan2TSP0pS3KW4UtwnyJ3KkyVJncqzO5TmdzB1yV5ncrTO5WmdyfNFz1kozJLvjzkiHukl+6dytM7lKZ3F+2qSPIk4HdueQki2XEMF73MCYHUtayVJkqTJUmSpMlSZKkyVJkqTJUmSpMlSZKkyVJkqTJUmSpMlSZKkyVJkqTJUmSpMlSZKkyVJkqTJUmSpMlSZKkyVJkqTJUmSpMlSZKkyVJkqTJUmSpMlSZKkyVJkqTJUmSpMlSZKkyVJkqTJUmSpMlSZKkyVJkqTJUmSpMlSZKkyVJksaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaFjQsaCHtBCF7QRjjwghGEdCMe1GQcIftBGH7QRjCD5exyHzPifA/B7CZlM44nODnA5xHOM9RzjPUc/Mz1G+H1ka/WRr9ZGv1ka/WS5PWS99l77L32XvsuTGTPISeQk8hIj2B/vKOZc9S56iPn1L3qX/Uv+pf9S76l/1F+3Kk8ieRPInk5PLTuJ+LnO55idzzE7nmJ3PMNzzDc7RI83O5hw853PJzueTncXuzueQbmD1m55KdzyTc8k3PJNzyTc803PJNztUtzzbc823MLrtzz7c8+3PPtzz7ct9GJRn6UZ+lGfpRn6UZ+lGfpRn6UZ+lGfpRn6UZ+l9oxPPty30Yk47RiY/Xbnm255ludxlueSbnkp3PJTueSncufOdzzs7nke550eZE7zLc8w3G787nl53PLzueWncwetJjc6SMf1Mf+PkxbqTnSng+byYiepwHF1IR7upwI4uslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1kuOslx1n/QT7Hu/wDsxykg+Z7ZPh6M/wBHvnIn2k90k+xNdT2Sfg9vIn2J9oJ95PYfI+RPsJHsPcPdJ8iD2VY9nI9knuk9h7j5E/g9hHxkT7ydz2HsJ9ifef8AjZI+cvw+RtNM+5PkfAj2gj2/3zYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYajYaky/wDtjgNcywT97nuJcUOXuT4TOBPsQ97kuZ9CeD30J4dhLdncl+Hyt3J45/I3tODtJ6liWeK3cmSX07kymXV1G9vc+cUdSeJ8Tp8kvR+k8E0akvV+k/GrqTx1fYzjX19Td7kfP0on3k+JJ45BwMHwm+EE/L4FPp3SvCrqVeRNHcUeRKVfpKr8dyjyJXsJlHYP4kzjtJnHYNTuNTuZyfiyU+Re5E6f7mSPnL8PkbTTPuT5HwI9oI9v+7+B+/T7/S/PtJ7BPtJP6T7ST7ST8Hs9JJ9+cek+0k+5PvBPsST7+k/HpPz6T7nuk9h7ifaT3cvSfkj8J9yfcn9HuJ9j2en49J9vT4Hw9J9z3EHuPZ6z7yfJ7CfYj4y/0sL5IiF7R0I4EqOhKf1QQr8I2IX+Q4h9ERwfqbDH5SH03QlKi5DXvcKOBwNJyOe/nYEo1NsmzDnrwJtTofIobHBV3Ik/MT5PoTY6fFMiI+LiaTSJphuNsVx+ioP0L1nQhFN0/wBMMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYM/5UYMGDBgwYMGDBgwYMGDBgwYMGDBgyfaSfw+B7oNX+np9xPvBH8R7evy/wCE5MVkZIGImTMjA1I+L7gqZY1I+CNSzr3LFdSx9blBHDwcoIgnfBEHz03I4XF1jciI/LTcj2OJnuInEe5lIRKGJE+4UTgKIx85Qzog4sOIjYP4wMQQREKfTKEFDfkcFPpjC2BiH36QxtsIas/BRU+BYIa5CEPjN8TxOG+sIe16XWj+Z+PLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePLx5ePJgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBhxjgU/SlHcpR3Hw4vxeD2/wVI9B8uNdTMPUDYkRcV4ERCnl/wmHr7v4j39fZ6R7x6x7R6x7R6/MHsI/iPb0j4Pn0j1j59Pd6PhyNCCPk+BJcCfAc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEc4jnEY+RzOZzOZzOZzOZzOZzOZzOZzOZzOZzOZzOZzOZz/wB9h6Pgew1z7HwPaT7esr59DDDDDDDDDDsHYPwMk1zMk1zMk1zMk1zMk1zMk1zMk1zOD5VzKHkwZVzHhHhHhH6Q8A8AwcPlyKDuUHcj5Q8h3H3Hcfcdx/OTn3PLdzy3cffdx993I2Ce5QzuUPc8j3PI9zyPc8j3PI9zyPcxer3Lhz7lF3KLueS7nmB5geYHmB5ruXTn3FfhI8vItfOR5EeRHkR5sefkXPnI8zO4nvbnm+5h9bueYEd47nm+4qv9KHuUPcTT+ld3K7uYVfMt1Zlf3KPueH7iKf0oY3LPRG5Y6IEfHRBb6IE/HRBY6I3MpyjcXw3FSgey6gfHogwp6IL89BGODCl0F30gu+gY9/TYcPx/HwJHy4WjYkyZzMRhr6BiHSNjwEbHuX6QRjOkFyF2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F2F3/phgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBgwYMGDBh/wDOwYMGDBgwYMGDBgwYMGDBgwYMGDBZCRhxICKIibsiOgmJTxkCOIib4Q/cBMyXyZkT8nKmQp/HediYffrTsTD8nOdhfzHKZJlHvMVyMxMQKMBRgREMW4txLiYyXNILmkFzSC99EQmoM7QuToXJ0Lk6EXtC9MdI/C9M83+FRbHkCcTqPIHkCMSeoz5nr2PMdjzHYiT5dexdkX8yRi8hd6zzh5wbudjyEF7QXuqC91QXOqNi51RsXOqNiMWOS2KWNiljYrI2KyNitjYrY2I9n6di7p2LmjYoY2LmnYudUbFzqjYudUbFpymNjyUbHko2PJRseSjY8lGx5KNjyUbHmo2I79Gx56Njz0bHno2PPRseejY89Gx56Njz0bHno2J+PrRseajY81Gx5uNjycbHko2PJRseTjYmKf1jYoI2KyNigjY8jGxg6Gx5GNhPZ2J8LsUsbHlI2ON+kbCvnQRiaDyEHkIPOdiX+5nPYjFnqcDxnqK8Jnr2OMM8Y0IpeDFTXIuhViJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgJgW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9S3qW9RL2MJqRjjwInPA4gKa9iIjBFLIRHM+Z8T4E/hKUTMqeIwww4DYQNkXPooUFCgoUFCgoUCPEFzQvRoXo0L0aCfn6L+nYpR2LunYpR2K3gr+Cv4Ky2Ky2Ki2KC2FeGxQRQRkeUSYOhsUYjYoxGxneUFSIE9sFaIMDRGxHgxsUYFGIKsCrEFWIK8QU4gpxBSiCPg0QVIbFWGxVhsVYFWBVgVYbCOyNiJezYJ7I2KMRsVYjYqxGxViNirEbFWI2K8RsV9gr7BX2CvsFfYK+wV9gr7BX2B9D6J+XRsKsRsVYjYoxGw3tjYrRGxWiNipDYqwLzoKkRBUiDE0wU4gpQK0C16DxYgqRBhPygZ76YK8QT8bcoKkQR4cbEcGyxoXxBwmzYks4l+IxkxRiT3L0Igj30Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmhc0Lmh4+Njx8bHj42PHxsePjY8fGx4+Njx8bHj42PHxsePjY8fGx4+Njx8bHj42PHxsePjY8fGx4+Njx8bHj42PHxsePjY8fGx4+Njx8bHj42PHxsePjY8fGx4+NiV+1yjY8RGx4iNjxEbHiI2PERseIjY8RGx4iNjxEbHiI2PERseIjY8RGx4iNjxEbHiI2PERseIjY8RGx4iNjxEbHiI2PERseIjY8RGx4iNjxEbHiI2PERseMjYWn6QpP0hGOPybEByo9iY/WcRQcipH0TNSaDU0HImk/on3KvImj/AKJlr9PRemn/AK9H25zTtm2S2v6HFU/IQoehLfbowKWfRKaDoS6q6HE8CrAlR3wYSntkntXYn95BP8WxwvzbHFrojh/i2J4vsZQeCJ4sJ09AlHscoJiI+Ogon4R8/wDGyR85fh8jaaZ9yfI+BHtBHt/vuY5jmOY5jmOY5jmOY5jmOY5jmOY5jmOY5jmOY5jmOY5jmOY5jmOb1cjkcjkcjkcjkcjkcjkcjkcjkcjkcjkcjkcjkcjkcj2JlHuE/eMSRMjl7icZjiS0TE8ZJlYfj7EzmPjUmxXIlfMVyJn+ersS/wB+qdjE8xONEnE4ryTM41JJiiePVOxIn9Z2JPDVnYn2ft2J96erOxMu7OxK5jWnYmfdnY7nnYn2dadj3epOx3NOx5CdjivkvOx5aRxH3SPLSJ4ka0jzUj2GtIkcNaQrjrSJxLnI85OxMO5Ox5SdhXv1Z2KGdiYo3dhXv1J2KyT4HqJg4TKVYuxXImP5hZ9ibFciIq7GIP7OBonYmDMRPxh/uZI+cvw+RtNM+5PkfAj2gj2/7v4H79Pv9L8+0nsE+0k/pPtJPtJPwez0kn35x6T7ST7k+8E+xJPv6T8ek/PpPue6T2HuJ9pPdy9J+SPwn3J9yf0e4n2PZ6fj0n29PgfD0n3PcQe49nrPvJ8nsJ9iPjL/AEsQyMDWSJU41MA+a/SE7pInjjrncib31TuRJMVOp8l6MSJ541MyRymle7V4kpHSPcbgmRI9wJ3uXEnuPIUfM8seT6bBldIZ0RMkOeaQ/iv5tJvv6n3jwv8A1nvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv/8AxPvvvvvvvvvvvvvvvvv1D+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+lQ/pUP6VD+kqoevqdEUhqcwJxMwpTjAM+nh6XeksECjOwYxy8VYlFfvr0DModTv296MpOWZCigfsn5TzC1f/AKVv+mNV3MKjufLNDc75uF9oxO47hQ36d73Cov0mkPspL9J9+uz9LmfNz+m3dZk/J1o/5OfcxoM5bl45tzzbc8s3Jm3253WW55idz5NaRu8X/Uv55lxqXPsxJ+x/KU8xzfqzmsyM3/8ANMMU4CnAVlkKb/Y2E9BsNBviHykyHKTCj7LM6lqS+ku+hf8AQv8AlBDnj0pIjBcxHs+wSW42IjhQsRBHGKViIJnhWsMVtChfwiOfcqB7+yKD7lIiXxkhe46EicP3N8e4SwYtFgrgFh+/vegcvaFoKfxEiJjIY4imZj4vn3vInoudWOo/6PqxYsWLFixYsWLFixYsWLFixYsWLFixYsWLFixYsWN/+tt27du3bt27du3bt27du3bt27du3bt27fgvwX4L8F+C/BfgvwX4L8F+C/BfgvwX4L8F+C/BfgvwX4L8F+C/BfgvwX4L8F+C/BfgvwX4L8F+C/BfgvwX4L8F+C/BfgvwX4L8F+C/BfgvwX4L8F+C/BfgvwX4L8F+C/BfglfvAl8VyMVGuxLjHDPYncWPfjjFjExXI+bDUl3xPoRKmNaRrdSJRhIpwFYiFP8AwmHr7v4j39fZ6R7x6x7R6x7R6/MHsI/iPb0j4Pn0j1j59Pd6PhyNCCPk+BhOEY5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xHOI5xInGR4GQyGQyGQyGQyGQyGQyGQyGQyGQyGQyGQyGQyGT/AH2Ho+B7DXPv0PaT/r8zhjIsUxWREfOVchYlchJxly8C3ngu65FHwRieXgoZ2MbpzsePkeNnY8ZIppHB42uEjEdaJEfN0BHbjjLyOLBykx2KT4KT4aTjsyPipMYJykeFENj65Ed7CO3SI+dmY+cGPn6g+WGOB9o+VGPk6ox38Y+brjDo+0POj5wI7kHlgj5esCP7BBAh/wA3zxDtQIX6IiQTIbx9OtY4HtZ/Tlj1a5dMEyPS58xl+kGQzh6c/PmPJFHHBIbyXwiO7B87qCz0uIpfsPnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB84PnB//2gAIAQIDAT8Q/wB98ke5JBHpEjH6MiRjuOMRxiOMRxiJiJiRDEcYjjEcYiYiYjjEcYjjEcYjH/M+kkkxJMTgTImWBMsJJlhJM8JJwpJwp6E4U9CcCehOBPSScCekk4E6k47UnHdJJxXSScV0knEak4zpJOM6TsTjOk7E4jpJOI6TsT2Sdie2die3TsT2adiexTsT2KdiexTsT2qdie1TseKnY8VOxPbp2PDTseWbHlmxPdmxPfmx5ZsT3wT3geYbHmGx5RseUHlB5hseYbHmGx5xseYEd8bEd+bEd2bEdmnY7GnY7KnYjtU7EdinYjsUkdqnYjt07EdsnYxLpOxinSdiMZ0kjFamJdJMHPSSMOekmAnpJGBPQjCnoRLCSTCSJYERJEEQMgZjMRPEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfEfH/f/Iz4I9YHAxkTAg4HAxwIJiJiIOBwJiOMRwOBwMYxj9Ey/VekiETEjYDYEywGwJlh61aKOG5OB9Fos11LesblnWNyzrG5b1jcnD1jcta9ypMblCY3J8iNypMbk+dG5OK6xuXXWCfKjcuusblRbl51jcuNNy46xuT5Eblx1jcnEdY3LzrG5fdY3L7rG5f9Y3L7rG5TmNy767i767icX13F/wBdxe9dxe9dxe9dxe9dxe9dxGN67inO4pzuIxfWNy76xuRi+sbl91jcjGdY3I8iNy46xuXnWNy+6xuXnWNyMV1gqTG5FSNyhMblrWCMPWNyMHWNyMPWNyMOupGB9blr0ongRLAiQpI9PYYxwRMCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCf8FBHpH+VoYx/wCeRCEIQhf4QLOZzOZv5zOZ/wCBmMxmMxn9OfTuZ9O5m07mbTuZtO5m07mbTuZtO5m07mfTuZ9O5n07mfTuZ9O5n07mfTuZ9O5n07mfTuZ9O5n07mfTuZtO5m07mbTuZtO5m07mbTuZ9O5n07mczaGb0ZzOZzMZjP8A2QiYEQT/AMFQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xQ+xVUFFQVPsIP7EUT2IqkiiTLr2Ib417GTXsRbqZTKZCJIgQhCgUCgQhCEKDgcDgcCf4kYxjGORyTMjDDDDDEyHxHxHJniXi4XCfWC59FwvF70rxeKOBTwKeBd0L+kF/SNi/8AWxf+ti/pGxd07F3TsXdOxd07F3TsXdOxd07F3TsXdOxd07F3TsXdOxd07F3TsXdOxd07F3TsXdOxe0jYvaRsV8C/pBd0KUUcPWrxe9BiMQvF4fEcYYb0RM+sE+kekRAhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxkSMgZKREkSRJEkSRJBEkEejiORz/AE5HI5HJx/xoQhHAQhCEKwowFGAmAgmAowgUYQKMBRgJhAmECW6C4R0FwjoLboLboLboLhHQtR0LUFqC1BajoWNILGhY0LGhY0gsaQWdILOkFnSCzpBY0gsaQWNILGkFjSCxpBY0gsaQWNILGkFjSCxpBY0gs6QWNILEdILGkFjQsaQWtC1HQXCOguEdBbdBcI6CYQJhAmECjATAQUYCsIQhEwL0n1f/AHvyST6wR7kkE+k+/pBJH+WPckj/AJmSPSf+Cy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jLr2MuvYy69jJr2MldDJr2MldBD217FVQUPsUPsUPsUPsZNexk17FD7GTXsZNexl17GWuhkroZK6GSuhk17GXXsZa6GWuhFtdDLr2MmvYy69jLr2MuvYy69ivH05DJqZPRlMv8AAymX/D4R6EBjGMgkif8AdyR6T/4FPse31gn+YJI9Y/mPYj+Y/iP5j/DJH8QSR/ME/wCnn04kzIxyORziMNjI2Mj4z1L0j4yXJ6l6esk4k9ZLk9ZL2sl+epOO6yTjtS66yTiiXHvPWS5PWS5rJenUvTqXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6yXZ6z/AMD8ekE+sEe/8T7f8WhCkQhCEKRhh/7goksgyTLMky+pl9TI6k2upkdfTyK5GT17E2+vYmz17GX17EYpXXsVvsVPsZHXsZFcjIMgyDIMgyDIMgyDIMgyDIMgyDIMgyDIMgyDIMgyDIMgyDIMgyDIMgyDIMiuRldexl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9exl9e3/BgAAAAAAAAAEZ/TH8AhCERH/FR6T/UE/1H+CSPSSPSD59J/wDBcesf5uBwOH+eTicTicTicTicTicTicTjiZhBBBBC5Fci5Fci5Fci5FchWVyFbXIpqCmoMmuRdiuRdiuRdiuRdiuRdiuRdiuRcguQXILkFwuFwuFyuhciuRcroXIrkXIrkXa6F2uhdroLHXQWOK5CxxXIWOK5CxxXIWOugsddBY66GeuhnroZ66Geug8UDxQPHA8UVyHiiuQ8UVyHiiuRciuRciuReroXa6F2uhfroXS/XQv109G6XC4XC4TPEccccf8A3v8A/wD/AP8A/wD/AP8A/ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndI3M7pG5ndO4zHpG5bnTcszpuWZ03OLKdNyxPSNy1PSCMOdCMOdCMKdNy1JFwS4onpEBBPQoEe/oQQQQQX+CZGMYxjHJPqGHHHJmMMPYaw4449hsBsBhrFqC1BlVzLRYLBYrqWK6liNdyxGu5ka7lr73LX3uWo13MquZlVzMquZlVzMquZla7mVruZWu5la7mVruZWu5la7mVruZWu5la7mVruZWu5lVzLEa7liNS1Gu5Y+9yxGu5YrqWK6j4QWoLUDWInYew4wxEhx/S3obGP0n0XpECEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGRIyJGSlETJEkSRJEyRJEkSRJEkSRIxjGORjGMYxjkcjkc/0hCEIQiYEEEEEEJiLgKKLgLgLgLgWCwWCx9lj7LX2W/st/ZRxKHJQ5LeslnWSllbkp4lbncr47lv73LP3uWfvcs/e5Z+9yz97ljWdyxrO5Y1ncsazuWNZ3LGs7ljWdyxrO5Y1ncsazuWNZ3LOs7ln73Les7lvWdyvjuVuSllLLf2VcSviW/sq4lgsFgXAXAXAUQQQUCJgiPSf4iRjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxjGMYxlhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYaFhoWGhYdILLpBbaEyfDpAj4dI2IwnSCy6QW40LekCPjQtR0EwgUYCgmCP8ALHuSR/zMkek/8FnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzM6uZnVzLE1zLE1zIXxNcyMGdNyzOm5b+i39blv63Lf1uWp0LX1uWJ03LE6blidCzOm5ZnTcsTpuWJ03LU6blqSxNcy1Ncy1NczOrmZ1czOrmZ1cy1JakjAkzBLiXFFFEuZglxBRBfSoognoT1IDGMYySJ/wB3JHpP/gU+x7fWCf5gkj1j+Y9iP5j+I/mP8MkfxBJH8wT/AKefRkzIwww+JOMXPQnGLxfKVBf0gu6RsXdOxc0jYvfRe07E4mkbEw/OkbE4+kbEqOM6RsXdI2LmkbFzSC5pBe+i99F76L30XvovfRe+i99F76L30XvovfRe+i99F76L30XvovfRe+i99F76L30XvovfRe+i99F76L30XvovfRe+i99F76L30XvovfRe+i99F76L30XvovfRe+i99F76L30XvovfRe+i99F76L30XvovfRe+i99F76L30XvovfRe+hMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTCBMIEwgTATATAn5ogsR0gsR0gjAgsQRgR0gsR0gs6QWdILMFiOkFiOkFjSCxpBY0gsR0gsR0gsaFiOhagtQWoLUdBcI6C4R0Ft0Ft0Ft0FwjoLhAmECYQWILECYQKMBRgIKBRgKBRgKBQKBCEIQhQIUCF6L/AO9SKRSKRSKRSKSZYDYDYD4FotFgnALZZksyWyyWJLUk4wnFaF0XxNCNy4rmTjK5k4zTcjuItFr6LRQ8lTyVPJU8lTyVPJU8lTyVPJU8lTyVPJU8lTyVPJU8lTyVPJU8lTyVPJU8lTyVPJU8lTyVPJU8lTyVPJU8lTyVPJU8lTyVPJOBXUsV1LFdSxXUsV1LFdSxXUsV1LFdSxXUsV1LFdSxXUsV1LFdSxXUsV1LFdSxXUsV1LFdSxXUsV1LFdSxXUsV1GGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGEMP6I9CPQQiIER/xUek/wBQT/Uf4JI9JI9IPn0n/wAFwR6R/sJOJH8ScSPSTicTicTicTicTicTicTicTicTicfRxHJx9D+Awwwww8FdR4a6jwjwwPDXUeGuo8I8I8MVzHhiuZZiuZZiuY8EVzLEVzLMVzLEVzLEVzLEVzLEVzLEVzLMVzIwYrmTgxXMjBiuZZiuZYiuZYiuZOBFcyfliK5jwwWYrmWYrmWYrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmWIrmf/9oACAEDAwE/EP8Aez/E+qF/KJgQhCJgQpFOApwFIwpwFYViIF6oQiPSCPSCPRkTA4xImMSIYiYwRDEiGJEMYIjjHUvR1Ixo6kY0F7UjGgjEjrBGJHWC5HWCMSOpGLGhGPGhGG6wW3WCMN1gjDdYIwXWCy6wWXWCMB1gtOsFp1gtOsblp1jcjCdY3LTrBade5GE6xuRgOsbkYDrG5Zde5Yde5bdYLDrBYdY3LLrG5bdY3LLr3LDrG5YdY3LDrBOA6xuWXWNyw6xuWnWNycJ1jcnCdY3LTrBOA6wTgusE4LrBOC6k4brBOPGhOLHWCcSOsE4kdYJxtScaCcaOpMMYJhjBMMSZjEmYxJn1n0QJsMhkMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMplMpl/4KRC9FJMCEIUikUikQhSKRCkUjESEMKRSIUikUkfxEfw4HAxwOBwOMRMRMRMRBBcfsXH7L32XvsuV0Ln3sXK6Fyuhe+9icbSdi/wDexf0nYv6TsXdJLmnYv10Ix9Oxe0ku6SX9J2L2k7F7SdiMTSdihE7FSOxQidihElKJ2KESUGUGUIkqROxUidipE7FSJ2KkTsVInYqRJUiShE7FCJ2KETsUI7FCJ2L2k7F7SdicbSdi997F/wC9icXTsXfsv6TsXtOxc+y597E4hOMTjC4iYkwxExJmBkz6THpAiYk4iEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCF/wAHPp8/yhCEIQhC/hCEIQhEeqEIiP4mf5YxjGv8ADKZTKZTKZTKZDIZDKZTKZDKZTIZDIZTKZTKZTKZTKZTKZTKZTKZTKZTKZTKZTKZTIZPRkMhkMhlMv8AcD9H6P1XrIhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCM2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzNp3M2nczadzOZ9DNoN+dO5m0M2ncm+upm07k36Gf+Ehfy5GP+p/qJ/iP8HA4Cj0IQQQoEEEEEFFEEFIjgWhcBcC0Lh6ggmBYEELH8gFotFotFotFotFotFotFosFgsCCEx9K0WicIUXAQQQQQUCj0KP6f/gEi9fd6SQJJ9JJ9J9JgQhHA4CEIQhCEIQiI/iSI/ljGMZx9OJxOJxHI5HI5xHOI2I2I2I2I2I5xHOMjnGRzjI2MjYyNjIw1xrjjjjj4jYz1GxnqNjPUbGeo2MjXGuNca41xrjXGuNca41xsZGxnqNjPUfEca42MjYyNcc4yNiNiNiNiNiOcRjk4nE4nH1f9IQv/AAD5JIJIJI9Pgj0j/JJBJH/Nx/4CAAAAAAAAAAIQvRHo5jP/APQAAABCEIX9IQv6QhCEL0X/AAMf+BwQST6T6z6R/wDcT6fH+6UCgUERAoFAowEwgTCBMIFwIjhBFroLhHQtR0LUdCxHQjCjpBajpBGDHSB72joWtC1HQtQRhQWoLUFqC1BagtQWoLUFqC1BagtQWoLUFqC1BagtQWoLUFqC1BagtQWoLUFqC1BagtQWoLUFqC1BagtQWoLUFqC1BagtQWoLUFqC1BagtQWoLUFqC1BagtQWoLUFqC1BagtQWo/30E/8lIxkEj9GOBwOBwOBBwIIKKKIJ/dQWYZhnkXzN6Gf0M/oZxndCL3QzOhFEE8dx9dz+hn9DP6Gf0M/oZ/Qz+hn9DP6Gf0M/oZ/Qz+hn9DP6Gf0M/oZ/Qz+hn9DP6Gf0M/oZ/Qz+hn9DP6Gf0M/oZ/Qz+hn9DP6Gf0M/oZ/QzehmmaZpmmaZpmmaZpmmaZpmmaZpmmaZpmmaZpmmaZpmmb/AMSAAAAAAAAAABAx+jH/AMJP+Cf7n0j+ZIJ/mD5/mff1n09npHqxjGMYxjGMYxjGMYxjGMYxjGMYxjGP0YxjGMYxjGMYxjGMYxjGMf8Awc+nz6ycTicTicTicTicTiIQjicTicTicTj/ABJxOJH9cDh/L9OBwOBwOBwHA4wHGA4wHGA4wGHA4wHGA8A8BYLBYHgmuY7h4B4B4B3DuHcO4d1cx31zHhmuY8NdR4R4a6jwzXMdfI8MjwyZZOeuZz1zOeuZz1zFhmuYsM1zFhmuZZmuYsNdRYa6iw11FhLJOCWSyWa6lgtFot+naLBYLBYLBYLBYIjAoooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooopdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdiuRdjXYvRrsXIrkTiRXIvV0IkwrkXI12MrXYnEiuRegmVhh7DjExIp/lyOf8sT/Eesx/cQIIIIIJ66iiiiCiiiXEEuJcQQW4txLkRuKLcW4pmGYZhmGYXC4XC4XC4XC4XC4XDMMwzBRbi3JjcS4txLiCiiii+sgggv4Xo/Vj/36EIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCEIQhCJgUC9IcRExBAmCYETBMekxB8C/hQKP8HA4HA4ekR6yR/DOJxOJxOJxHI5HIwwwwww4www+I4+I+I+I+I+JeLxcLhcLhcGGx/sACjgVcCrgVcCrgVcCrgVcCrgVcCrh/IA2JcLhcLw+I+I+I444www5HI5HPoxj9F/CF/vr+sl/WS/rJf1kv6yX9ZL+sl/WS/rJf1kv6yX9ZL+sl/WS/rJf1kv6yX9ZL+sl/WS/rJf1kv6yX9ZL+sl/WS/rJf1kv6yX9ZL+sl/WS/rJf1kv6yX9ZL+sl/WS/rJf1kv6yX9ZL+sl/WS/rJf1kv6yX9ZL+sl/WS/rJf1kv6yX9ZL+sl/WS/rJf1kv6yX9ZL+sl3WS5PWSMT7L0iPmepfak4k6lzWS9PUieMjYyNiMOSJ9In/ACSQSR/zcf8ABqRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRSKRCEL/INhvQpFIp9RSL0KRCkUikUikUikUi9EIQhCEIQhCEIQhCF6L/gY/wDA4IJJ9J9Z9I/+4n0+P9yhCCCCCERFwLXoWCwWvst/ZZ+yMDWSzrJGHrO5b17kYP3uOe32Wix9lj7IwSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSyWSz/voJEIX+ByMf+HicR/xx9eJxOPoxjGMY/R+j9H/AKORjIkkYxnD+nA4HGI4xExExExFxLxeLhcLhegvwXS7BGPBfgvFyC5BaEYT01kSJ9y+Xy+XS99l77L32XvsvfZe+y99l77L32XvsvfZe+y99l77L32XvsvfZe+y99l77L32XvsvfZe+y99l77L32XvsvfZe+y99l77L32XvsvfZc+y59lz7Ln2XPsufZc+y59lz7Ln2XPsufZc+y59lz7Ln2XPsufZc+y59lz7Ln2XPsufZc+y59iCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCf3AggnoY/V/8ACT/gn+59I/mSCf5g+f5n39Z9PZ6R6QMYxjGMYxjGMYxjGMYxjGMYxjGMYxj/AOdn/ecDgcDgcDgcDgcCEcDgcDgcDgcDgKDh6nAUCgmAggggsYsddBY66ExjmuQsddCIx10FjmuQsddBYq6FyuhOLNci7NchY5rkXZrkLFNci5Nci5Nci5Nci5Nci5Nci7Nci7Nci7Nci7Nci7Nci7Nci7Nci7NciMSa5Cx10FjroLFNchY5rkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkXZrkf/2Q==',
            'type'      => 'image/jpeg'
        ),
        'bkg_middle.gif' => array(
            'base64'    => 'R0lGODlhwgONAveVAPv69vr59fn49Pj38/b18ff28vX08PDv7PHw7erp5tLRz+/u6/r69vLx7u3s6PTz8Ovq5+no5cjHxcnIxuTj4PPy7uHg3ebl4uXk4fX08cjIxvTz79PS0MrKyODf3Ojn5O/u6tbV0+zr6PPy7+Lh3tPT0O7t6e7t6vLx7fDv69ra18nIx/Hw7M3Myvb18ufm49HRzt3d2uzr59fW1N/e3N7d29bW0+Hh3vb28vT08M3Ny9XU0t3c2ePi39va2MrJx8vLycvKyM/PzdXV0t/e29TT0drZ19LS0Ovr58fHxdLSz9nY1d/f3N7d2tDPzeDf3enp5evq5u7u6tfX1M7Oy9jX1dzb2dvb2ODg3ff38+Li39jY1fDw7MnJx9nY1s7OzNzc2ePi4OXl4tPT0ejn49XU0dnZ1urq5u3s6drZ1fj39Obm4+Lh3/f28+/v6+3t6ePj4M7Ny+bl4dzb2OHg3tjX1Obl48/OzOzs6PHx7d/f2+jo5eLh3d3d2efn5NHQztbW1N3c2ubm4uXl4d7e29jY1NHQzd7d2eDg3Orp5dvb1/Pz79DQzvr59uTj3+jo5NLRzufn4+Pi3uXk4Nva1/v69gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAJUALAAAAADCA40CAAj/AAsswGDESZJKCBMqXMiwocOHECNKnEixosWLGDNq3Mixo8ePIEOKHEmypMmTKFOqXMmypcuXMGMqTOLECIYFBQReUGFQps+fQIMKHUq0qNGjSJMqXcq0qdOFNFVcwFngwAVKjA4+3cq1q9evYMOKHUu2rNmuSRhRunCgQJYDL3xkPUu3rt27ePPq3cu3r9i0Pl4cyDIAQaQ5f7T6Xcy4sePHkCNLnhwzyZ85kRAMGJDng5XElEOLHk26tOnTqIdatvIhz2YUjz4rTk27tu3buHPrvrv6EYoBAhpE4AFj9u7jyJMrX868OcUkMHhEaCBAQIXhxZ1r3869u/fveqFL/69QvUIC4sbBq1/Pvr379yLFJyAvYMR5GBLg69/Pv7//7hJEl8AI1dlHXH7/Jajgggw2yFiAPAxY4H0IOmjhhRhmqCFREEoowAYUbijiiCSWaOJFHW5QHYgHnujiizDGyGCKK4Yo44045qgjczR+mEAM+O0o5JBEFjlZgDEkoKKPMShQoZFQRinllF9JoECSS4LY5JNUdunll2C+ZCWWNW4Z5plopqmmRmMqWd0DPzq55px01qlmmw+8GSeXdvbp55854qmnmYAWauihIwoqAJyEIuroo5DypyijckZq6aWYbidoAJTymemnoIZqGp4BcLqnqKimqipkpJra6Kqwxv8qa1mtdjrrrbjm2lStp+rq66/AxlQrBE1UGuyxyCYLkpVNQPBAqTkQa6yy1FZr7UPMQpADtNJ6eu234Aab7bYBRFvDtOGmqy6uVtagLbfnervuvPR+2u675UIQb7389pvpveRGSwO6/hZssJ9W0oCvwAQf7PDDZya8MAQDywvxxRgXKXHAFDec8ccg47gxtxWHbPLJMo6cb8kot+yyiCozbPHLNNesX8wdz2zzzjx7h/PAGvQs9NDgaaCAwhwDTfTSTDNnNNIkKxB001RXfdvT+BrQ8dRWd+31aFgbUKrWSn9t9tmPhT321mi37fZeagegNRNSv2333WYZzQQEYsv/DQHdXOMt+OBP6c332EgwoUTghDfueFEaKMEEEn0bkPjij2euOVCRT1755YxvLvroJ3VOOeKKh0766qx3ZPrnqbcu++wavY465rTnrjtEtssN+u7AB49Q75YzwYHqwidPugYceI64B8crL33rzHtwuu/QIz/99oRXf73l2XMvfube952BCOGPr/7g1YuQQannp7/+/G+3/34A8UdP//5o2w8/+vrjnwC75j/8AVB7A0wgzwqYPwQq8IEvY+ABIUjBnhWQABOsoAZp1j4ClAqDTwjgBkdoMuY9QQQeDAAIRUjCFmLMhCj8oAhCOAEX2hBjE+DACVO4whre8IcHy+EO/2VIQyAa0V9CjKEKZ8gBHx7xietKIg+Z6EQoWvFbUiRiE6/IxWtlcYlPKEEVu0jGYE2gBENcogXEWMY2mrEEFlAiBtc4RjfaUVZnjCMPHUDHO/pxVnl0wB77+MdCqiqQg2SjIRcZKkR+kI+KZKQkL+VIFUKyjpPMpKEqSYBLavKTiOKkJ0FJyj+JkpClTCWdThlJVboSTazE5CtnOaVY0vKWXrIlLncZJU6aAJW8DKaQ8mgCHv6ylcJM5o2IaUxgKvOZL2LmB48pS2hac0PSVCE1r8nNEmWTANvspjg19E0T3GAM1RynOv0zgTHcoJgfPME507nOesKnnTc4AQ/lif9Oe/rzP/jUZzzn+c+C7ieg+ySoQRfaHoSWygX8pCdDJ7qcgLrgoRGlqEa7Y1GM3qAIK9ioSJ2zgiLk86IBgOhHQzrSlianpCf1KEhdStPdwPQEKFXpTGvKU9vcNKfy3GlPh3qan8qUpURN6miMmtKgIlWpUJUMUwvg1KhaNTI3LUCpqEoCoV71q34pKQlOoNUAFAAEXX0qWNeaF7GCoKxnJcEO1MrWutJlBTsgwVu3ita52vWvdcGrXuHaV7oC9rBfEexezVpYxDo2LIolrFwN+9jKLiWyfCXBEChr2c4aZQVDGGxmN+vZ0ioFtKJlrGY5a9rWygS1i40raV1L26D/wFays62tbmNy260uQLNd2K1wX9KF0OLEt5IYQnCHy1yVFFcSxzXrAnqg3OZa9yTF7UF0BULd5V73uyHJ7nanW13wmtcj4oUreb173vZiJL2+7UEI2Ove+k6kCyHQrnrhEIIf2Pe/E/lBCOAwXv76F8AIdoiACQxXNxg4wRBeyILd0OAHR/jCE65wfy+M4QFTeKtuoMCGOQxhAVPgw2YN8YhJjGATo7gqIj4wiwFs4rZs9QAxnnGLQ0ABG5sVxyvWcX1rXNYBAFnGQnZvjQdQKiPnOMlD5vEBmAwAJwcZyuZdMgCqjGMbIBnL4P2BDXo8gC072QYdAHN7OzDmKZu5/8tpVrN52UzmN1MAzXKec5vLzOU7xznP16Wzm/uMZ0AHes9vxkChDd1cNmNg0EZW9J8ZPVxHQ/oAkqZ0o23waD5HetGa3q2lPY1pUIe6tqNOtKlP7dpUcznTrNatqyM9g0nHurUdmEGnE13rW6Na15BmAQZ67etW65oFnhY2sYtt2lxjANlmVratmW1ZZ0O7ytKmdrOP7WkEDHva2nasszVjZm8vO9yVHXe3v43uzqq73Oxud7p1Te4qI0AM55b3YXMthnoXBt/g1ndd+e3ve+db4HYleLcBjnDEKrzcYphCwBv+1Q5Mod8Ln0IQKA7YIFy84BHfOMft6nGMl/sCGv8fOcmncIGCo1zkKl+rx1ve7ZfHnK0zd3nKbw7WnNd85zy/qs9PXgWYBz2qQagCzYlu9KMrNelLrzIKLlB0p1sV6r8x89SrbnWoYt3TW29613v69S0LIOxjf7rSUSAAs6M97UTFetsBEByqAwHuRAWC0qlj9gZcYAt3x3tPgbCFC/Cd7n4HvOAHX/jD113xi68p4Q0/98cHPvIunbzjE395zI9U85XnvOcz3/jQrwHyoxcp4dew+TUsofOppygQlsD6ylfgBa+PveqX8ALymP32ude9Rmffe9vjHvbCNyjxfU934CM/+f9cvvGDD/2FSv/3ZEhDC6rP0BakgQzMt07/9rfPfYN6H/y2H3/5zf/98FdA/ev/5/ndD//423P+6de+/f2Jf+wbgfz7t04tYATo93sf8H8BWE8D+AHu9wEqAIAJKE4toAIMaHsOCIERyE0TWIFmtwEXmIHjtIEq0oEfCILdJIKV54EPaIInSIEjSHcqiIEsqEwo2IERsIIzaE0TGAEv+CE3KIM5GEw72IMb8INBCE1DmIJGeIQ0qAI8qIQ4yITClIQ2GIVSyEtUCIM3qANXKEw64IREuIVdGExf+IQ26ANcOIa7pAM+YIZaiIZquIZtGIZwGIe3xIZuKAAGEAF1aIezhIcGUHl72Id+6EqAKIhQQIiFmEpsCAWB/2h2BpCIabiIqtSIj0h3kaiIlAhKloiImriJmtSJkCiJoMiIPuCInjiJpfhJooiJpLiKnHiKj1gJeviKsBiKsigACFGLV6CKtyhJOnAFqLiLBpAAVxAHv6hJcXAFCRCIxGiMyJiMk7SMzaiLtFiMxyiN08iMzniN0KiNkkSN3Sg33wiOiySOAYAQ5HgFVGCOi0QF3JiOlbCO7eiOhQSPzSiP5GgF9WiPfkQFVpCP6liM/OiPfwSQAjmPBNmPBulGCCk26pgBCVCQDWlHCPk+EXkGFFmRbQSQZ4CR85gBGsmQHNlFHgmS+HMGYPAFJdlGXwAGH6mPBKCSLNmSZPSSZ//gQeo4kytpkzcJkzo5jzPZBzXpk1f0BX2QkzIJAURplFyElBAQlCrElEXplE8ElVJJABBwCFVplUb0BYcQlUt5CHfglVB0B2GZlcRSlmZ5RHfQLGrZBGzZlkD0lmK5kzIgl3RpRG8pA1mZl0Kwl0AkBE3glzIJmIL5Q4RpmDspAk0QmIlpQ4QZQ41ZA5AZmS0kBDVAmUIpApaJmS6kmZy5RJ8JmiQkmlnpmZdpmhqEmjKpmqw5Qq65kw5QmrFZQZopSDLpAISwmrf5QEJACLpJm735m7gpnFnpAETgm8Y5QEJABMMplMrJnM3JP88ZnQGAA9NZncAJnTigj9pJBE7/wJ0K5ATeCZ7KOZ7kOUDm6QDfqY7hqZ7ryT/t+Z7zGJ/zKUD1iZ7imZ/0eZ7wiQb96Z/0Y55oYJ/ZiQY0IJ8Eqj5OQAMHCp4KaggNOj+GAKEIigMmQAMUWqHqc6EmkKEbCgMeqj4wQAMhqo8FYAIeQKIlKj4w4AEmoFXquKIt+qIwKqM0Oo826qI4Oj0xOqMqyqI++qPKE6Q7alYncKNGKj0xSlYquqRF2qTB86RJSlVMSqXCY6UqKgUeAAlamjyQ4AFScKVeqgBhKjwKQKZm6gFomqbAs6Zl2qVuCqdxyqZ0+qZ2mjtyaqZYoKd7OjsKgAVzWqMg8KeByqdYsFc1/7oAiJqogooFx9WojwqprTOok8qjjgqolko6mHqljqoEndo6SiCpoGoBojqqq6MEFpCpAdAGC4CqqrqqrdoG+jgAsXoEs0o6R9CqTKaOuMoHurqronMEfLAAvzqPwTqsxKo5xoqst7oAwtqsm/OsyRoAA5ACJMCs1Oo4R0ACKXCt2bqt3Zo53xqut6qt3Fqug3Ou4qqu7Oqt4Pqu5BqvhOOu6Vqv9io4+AqsKcAG67qvb3MEbICu/qoFHCCwgsMBWmCwynoACKuweMOwU3arEJuwEms3FCuuF5uxGqsFFQusHeuxbrOxFhuxJNs2JiuyKJuyZ7OyD9sDJeCyaFMCPf8QsjE7szRrNjaLs9h6ADK7szx7s+LKAmFQBEL7NUUQBsh2q0ZbBknrNWXAtEUbBlAbtVYztU0LrE+LtVlLtfLIAAPQtV5LNVo7AAyAEGJLtmXLNGebtpUgtghgtW3bNFOrGXArtxSwA3XLNDtAAXirtoWxt327NH8buHE7uHxbuENzuGgruAhAuIwrNI6bt4o7uZQLuI+buJG7uJi7M5WrtgLQuZ/LM4crAHkrAHkguaVbM3+bB6gruqvrua37Mq8bu3Gruqxbuy5zu6nbALvLuyjzt9TxuxQwBML7MkNAAcUrug2AAcibvC0zBBjQvLn7vCEgvS0TAtWLuwwQHBj/kL3aezLca73fi73jS77d+7vhm74mU77eC77i674fA7+/KwfzS78YEwJyYL7Bgb/6W7/9G78NAMABvL8DfL/5e8AOw7/+63cLzMAGEwKU97sXEMES7C8U/MAXYAMZ/DA2UMHO28Ef7DAh/MCC4MElbDA2IAj+WwEpvMIsLAjkkboVsAaAIMMFAwhrUMOie8M5rMP9wsM+nLtALMRD3MPxe8MzgMT8MgNKbMMv0MROTC8zUHypOwJTXMVW/AIEksVbzMXrcsVfLLpaXAdivC514MW4S3dnnMbqssYEAgAI4cYvgMZwHC5y3HZ1XB93nMd6zMZ0XAl2jMeAfC17PMhu//wBhXDI31IIHzDHfTwCjOzI1wLJkkzI9fEBS2DJ1rIEkczHmkzJnezJ1ALKmbzIpWzKyYLKoqzKrKwsrqzIm+wFsZwsXhDKtOyBtnzLx5LLI9jHvOzLv/wBwazJG7AHvUzMv+IFe3DMMKjMzAwszgzNPrjM05wrXmCGwhwB2JzNt7LN1vwAEWAG4KwrZhABeULL5GzO54wr6bzOfdzO7wzP6vzKi1LO9Xwr8YzP9LzPstLP7KzPAB0rAj3PEWAEBR0rRnDPA63QC70qDS3PmkzOEB3RqTLRr8wpCY3RqjLRAaDIHH3RHh0qIC3SFl3SqHLSdUyOJK3SmWIE+SjSxf/40jB9KTItNjSdACpw06CiAjPd0sXY0z6dKUCt00LN00Vt1EFNyORI1EttKUcd0kkN1VENKVO901Z91Y6S1UIdBYrA1ZGiCFGA1E5tAGAt1pBC1mYNAHITBXOg1o8yB2VN1WcNAXEt14gyB4dD03it13vd10L914BtKHzd1vgjA2BQ2IYCBjLwPiKdAYrN2IXi2JDd0gQw2ZT9J47tQSKd2Yu92X7S2Xbt1pnNA6LtJzxgmJ8tA6id2nay2p6N2a4N27HN2rT92rY9J7Jd2ipU27vN27jt1ASAB7od3GnCA3gw28SNB4GA3GsSCMvt25303NCdJoGgm5/tANZ93Wf/kt3MbdoOEAPejSYxoN2YPd7lfSbnHd6WRN7rDSbtTd3qHd9fMt+fjQY1YN9fUgNo4N4EoN/87SX+DeACPuBUUuDU/Qb7jeBTUgNv4N4uwOAO/uBvcFEiPeENXuFQAuEY3tIazuFR4uG+HeIibiQkLtIruuEnPiQ1IKQqfgJE0OJFQgRQGuMzTuNDYuNaheM6vuM33tJUleM/riM87ttUpQdFviN6EOROfVZKvuQ5ogd7peIgEOVSfiNU3uNCfuVZjiNbjuQg4AFffiMeUOVdTuZlHiNnzuVPPuZrzuZo/uQLoOZx7iIecFwqvgCIcOcvggh6LuR87ucuAuhu7tYC/4EFhH4ipurbsKroi14ikmqrIg2rFhDpJVKrdj2Plo7pJKLpCvGqserpIwLqCREAWZACl07qG2IBKZAF8qiOqb7qrJ4hrg7roT7rta4htx7r8/gWtL7rFmIBg+HrqH4AwS7sDULsuH7qb0EHyn4hdFDsuX4A0B7tDjLtzS6y147tDDLtycrt3t4g4G7sRtbt454g5a4QjTAAXHAD6b4gN8AFA9AIClFl7x7vCjLvZXbv7k4C+p4gJEDvg1zH/x7w/zHw/Z4Q+A7wCN8fCl/whHzwD88fEe/vXODwFa8fF8/whcEGG78fbEBu/o4AIB/y8DHyC2/wJo/yKU/yHt/yLv/vHiov8fbWAzPvHj0A8yyP8znPHju/8hOPAD7/8+oR9DZfGEVv9N+B9PeuBkTP9EePAGpg81C/9FLPHTtf9U+PAo6Q9d/hCCjA9QyvBihAAWDvHRQw9lZ/9mnfHWtP9nVs9mj/9tsR9zZ/dnVv986x9qLcx27P933PdnnfAJMg+M4xCXx37+CL+M3RvYWPAY7PHJDP+M87+ctR+Qzf+JifHJrfx5ff+cjx+ZrcAIMg+sgxCIu/+aaP+seh+n9f+nLg+rsxwIV/AbSvG5R3+7mfG7vP+BWA+71/Gxfge8Av/MNfG8Uf+82H/MmfGsuf9xVgB89fG3Zg/Js//dVPG9f/z/zWQf3bjxrdL/3gH/6mMf7A/wLmfxrFl/davP6mIciM//7wTxryv/n0X/+icf+TDBAvKg0kWNDgQYQJFS5k2NDhQ4gRJU6kWNHiRYwZNW7k2NHjR5AhRY4kWdLkSZQpVa5kWfDFCAEACgIQMEJgS5w5de7k2dPnT6BBhQ4lWtToUaQuYcokSHOEn6RRpU6lWtXqVaxZtW7lStXP0pk1oXYlW9bsWbRp1a5l2/bq15hhn7qlW9fuXbx59e7lixMu04E0F33oW9jwYcSJFS9m3PPDorhNBQxuXNnyZcyZNW9W+zhyYAEPCHMmXdr0adSpVUv88OBzJZqiV8+mXdv2/23cnV0Dhh16dG7gwYUPJ14cZOvXsX8bZ97c+XPos5HzVh7d+nXs2bXbnR5W9nbw4cWPJ/+zu+Tv5dWvZ9/ePcTzoB/seV/f/n382ffs9h4h/38AAxRQtQj4Q8+/ARNUcEEG9yowudAQbHBCCiu00KoHqYvwQg479PBDnDLsD0QSSzTxxIpElMwACVF08UUYSYzAAAgNSCRGHHPUccFEaNTQxh2DFHJI9npMLgADEiBySSabhC4BAwKgLoAMlHTySiyzpC2BDKSciUortRRzTDIr49LLpsAsc00228zrzCmrdHNOOuskC84vM4jCTj779POoKLqMc88/CzX0UJYCRf8zMCoJRfRRSCPdSNFBJbX0UkwbojRPRzP19FNJN01TT1BLNdVQURnNAIJTW3V1TggEzZPVV2u1NctYF4WNSlpv9fXXIHONs1dgizX2RGHzlOFYZpv1UAZZR13WWWqrXRBaXQEIgIBprfX22/tkICDbbbsF91x0yROXXG7Tdfdd7dadsl1467WXOXm/JACPe/v1Nzc8xp3SBX7/Nfhg1PBwgVwXHED4YYgzc2DhgR2O+GKMEZuYYYsz9vjjuzauGGSSS15L5C8bNnlllrlCOU2VW5Z5ZqleZhSHjmnWeWegHMCBXJx5FnponXwGOmeik1Z6JKOnxMGEpaOW2iMTfnb/Guqps9aaoqqBxnprsMNWqOspC/habLTRNqEAcs1O+22x1277bLjrjlrusum2e++h8f7Sbb4D75vtvAU3fGe/0yxAisMbl1kKwv9m3HHKS4a87ckr1zzjy8sGYXPQLwYhcsU/D/30g0dv23TUW79X9SkHYN112t0FYQByZa9993Rvz3123oOn1vfYgRf+eGOJ/3KABZB3/tgFcI+9+eer9zX63Km3fntXsZ+ee/BP9X557cM3H9Px02T+fPYvTZ/R9duX/9H3d41/fvz/rF/b+/P3v879BaB//yMgmwI4wAImUEwHLJ8CHYglBj5QghCUHvkmeEEmHTAFGOSgkFJQ/0H1bbCDI8TRB3MnQhKmEEUmjB0KVfhCELFweS6EYQ0vJMMQ2lCHNwQh/Gi4QyAmCIc+DGIRFTRE+/3QiEu0DxL5p0QmRpE9ThTgAaR4xfccoIf2syIWvageLeaui18kY3jCGLsxllGN2Dnj8tK4Rjg+p43qe2Mc7VicOcKvjnfkI3DyyMU+BlI4f+TfHgV5SNUQsoqIZCRtFDkAQzZSkpp5ZCQnecnKPJIFmOSkZliwRf5tspOjrMwncydKUqYyMaaMHSpV+cq+sHImABiAK2F5S7x8EgDUoaUtcflLt+iSl7UEZjHpIsxZEtOYy1QLMpvSS2ZG8yzODAw0pXlNrqpQEza0RAA2vZkVBAxgl8ns5jfNSZVwjvOZAyjnOd2JlHQOs53vpOdQ4knOeuZTKPdc5zz1+U+d8LOa7ARoQQMqTnkaVKEsEeg2CbpQiJ6kobt8aEQtKpKJ0hIFF+UoSFCA0GRutKMj3chH1TlQkZJUpRYx6TBTulKYRqSlIY1pTSEy03W+1KY7RQhOqykAnfJUqANBQUx4CdShJpUgRT3pNpGq1KQylSkBAQA7',
            'type'      => 'image/gif'
        ),
        'bkg_middle2.gif' => array(
            'base64'    => 'R0lGODlhAQCBAdUAAPb4+eLp6/7+/eHp6/f5+v39/fn6+/z9/Pj6+vv8/Pr7++Pq7OTr7fP29+Lp7O3y8/T3+O7y9Oft7/X4+Oju8PL19u3x8/H19uLq7OXs7vD09erw8e/z9ebs7uvx8uzx8u/z9Ovw8urv8enu8OPq7ePp7Ofu7+Pp6+nv8eTq7OTq7eXr7uju7+bt7uvw8env8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAABAIEBAAaZQEZqUVo4TgFHYMkcBJzQ5mBKpT6XzqVSiXEsMNpAd0Euq0iMtJqxyrg7rU5HQpewTJT8iIJ6iUQbgSEhLh4eHx8PDxaMig8RkBEgIByVGpcXmRWbFQ2enw0QohATpRMAqKmqq6ytrq+wsbKztLW2qAS5uru8BAi/wMEIBsTFxgYKycrLCc3OzwfR0tMHBdbX2NgC29zd3ttBADs=',
            'type'      => 'image/gif'
        ),
        'favicon.ico' => array(
            'base64'    => 'AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAD///8A////AP///wD///8A////ANDa+kqEk/WXLUPv/zpY8f+Rp/au2eD7PP///wD///8A////AP///wD///8A////AP///wD///8A+/z9IqW2+IFBYPD/Kj7v/1d49P9IafL/JSzu/1x78+rl7Ptl+vv9DP///wD///8A////AP///wD///8Ax8/6Rv7+/b2ov/n/JBzt/z9e8f/r8v//tsv8/yQc7f8pOu//n7n6/9Tg+5Pb3/sq////AP///wD///8Ai5T1iUli8eX/////mrX6/yQe7f9AX/H/7vT//7PJ+/8kHO3/LkPv/6a++/+uxPv9Wmnyxamv92T///8A////ACg47v8yTfD//////5q1+v8kHu3/P17x/+rx//+xx/v/JBzt/y5D7/+nv/v/rcP77Sc27v9MWfDT////AP///wApOe79M07w//////+atfr/JB7t/z9e8f/q8f//scf7/yQc7f8uQ+//pr77/6/F+/ApO+//VGDxyf///wD///8AKTnu/TNO8P//////mrX6/yQe7f8/XvH/6vH//7HH+/8kHO3/LkPv/6a++/+wxvv1Kj3v/1Zi8cf///8A////ACk57v0zTvD//////5q1+v8kHO3/PFvx/+zy//+yx/v/JBzt/y1C7/+mvvv/ssf79ys/7/9WYvHH////AP///wApOe79M07w//////+Ys/n/Jiru/1Vy8//u8///vM78/y457/8wRu//n7n6/7PJ+/gsQe//WGLxxf///wD///8AKTnu/TJM8P//////rMH7/zVG7/9dePP/7vT//8DS/P80Qu//RmHx/7/R/P+yx/v6LUPv/1hi8cX///8A////ACg47v05VPD/6O/+//n7//+iufr/bYn1/+Ts/v+6zfz/VnHz/8fW/f//////pbz6/S0/7/9VX/HH////AP///wA+V/H/SGHx/2F99P+rwPv//////+fu/v/4+f//8PX//+bt/v/09///jqf4/1Rv8/9FXfH/ZHfyyf///wD///8Awsr5VGR48ttIYfH/UWzy/3GN9v/O3f3///////////+0yPv/Yn70/0tk8v9IYfH/don0wtfc+zP///8A////APz8/QHt7/wYoK33hUli8f9KYvH/WHPz/36Z9/9+mff/TGXy/0li8f9bcfLlu8T5XvL0/Q////8A////AP///wD///8A////APn6/Qbc4Pswf5D0tUpi8f9MZfL/TGXy/01l8fiZp/aO6+78Gfz8/QH///8A////AP///wD///8A////AP///wD///8A////APb3/Qq/yPlXYHXy4GyA88/W2/s4+/v9A////wD///8A////AP///wD///8A/D8AAPAfAADgBwAAgAMAAIABAACAAQAAgAEAAIABAACAAQAAgAEAAIABAACAAQAAwAMAAOAPAAD4HwAA/n8AAA==',
            'type'      => 'image/icon'
        ),
        'ajax_loader_tr.gif' => array(
            'base64'    => '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',
            'type'      => 'image/gif'
        )
    );

    /**
     * Print Header HTML
     */
    public function printHtmlHeader()
    {
        echo <<<HEADER
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Magento Downloader</title>
        <link rel="icon" href="{$this->getimagesrc('favicon.ico')}" type="image/x-icon" />
        <link rel="shortcut icon" href="{$this->getimagesrc('favicon.ico')}" type="image/x-icon" />
        <style type="text/css">
        * { margin:0; padding:0; }
        #body { background:#496778; font:12px/1.5 Arial, Helvetica, sans-serif; color:#2f2f2f; }
        body { -x-system-font:none;background-color:#496778;color:#2F2F2F;font-family:arial,helvetica,sans-serif;font-size:12px;font-size-adjust:none;font-stretch:normal;font-style:normal;font-variant:normal;font-weight:normal;line-height:1.5;text-align:center;}

        a { color:#1e7ec8; text-decoration:underline; }
        a:hover { color:#1e7ec8; text-decoration:underline; }
        :focus { outline:0; }

        img { border:0; }
        ul { list-style:none; }

        h1,h2,h3,h4,h5,h6 { fort-size:1em; line-height:1.25; margin-bottom:.45em; color:#0a263c; }
        .page-head { margin:0 0 25px 0; border-bottom:1px solid #ccc; }
        .page-head h2 { margin:0; font-size:1.75em; }
        .page-head h3, .page-head-alt h3 {font-size:1.7em !important;font-weight:normal !important;margin:0;text-align:left;text-transform:none !important;}

        form { display:inline; }
        fieldset { border:none; }
        legend { display:none; }
        label { color:#666; font-weight:bold; }
        input,select,textarea,button { vertical-align:middle; font:12px Arial, Helvetica, sans-serif; }
        input.input-text,select,textarea { display:block; margin-top:3px; width:382px; border:1px solid #b6b6b6; font:12px Arial, Helvetica, sans-serif; }
        input.input-text,textarea { padding:2px; }
        select { padding:1px; }
        button::-moz-focus-inner { padding:0; border:0; margin-right: 5px; }
        button.button { display:inline-block; border:0; _height:1%; overflow:visible; background:transparent; cursor:pointer; }
        button.button span { float:left; border:1px solid #de5400; background:#f18200; padding:3px 8px; font-weight:bold; color:#fff; text-align:center; white-space:nowrap; position:relative; }
        button.button_disabled { display:inline-block; border:0; _height:1%; overflow:visible; background:transparent; cursor:default;}
        button.button_disabled span { float:left; border:1px solid #bbb; background:#bbb; padding:3px 8px; font-weight:bold; color:#fff; text-align:center; white-space:nowrap; position:relative; }
        .input-box { margin-bottom:10px; }
        .validation-failed { border:1px dashed #EB340A !important; background:#faebe7 !important; }
        .button-set { clear:both; border-top:1px solid #e4e4e4; margin-top:4em; padding-top:8px; text-align:right; }
        .required { color:#eb340a; }
        p.required { margin-bottom:10px; }

        .messages { width:100%; overflow:hidden; margin-bottom:10px; }
        .msg_error { list-style:none; border:1px solid #f16048; padding:5px; padding-left:8px; background:#faebe7; }
        .msg_error li { color:#df280a; font-weight:bold; padding:5px; background:url({$this->getimagesrc('error.gif')}) 0 50% no-repeat; padding-left:24px; }
        .msg_success { list-style:none; border:1px solid #3d6611; padding:5px; padding-left:8px; background:#eff5ea; }
        .msg_success li { color:#3d6611; font-weight:bold; padding:5px; background:url( {$this->getimagesrc('success.gif')} ) 0 50% no-repeat; padding-left:24px; }
        .msg-note { color:#3d6611 !important; font-weight:bold; padding:10px 10px 10px 29px !important; border:1px solid #fcd344 !important; background:#fafaec url( {$this->getimagesrc('note.gif')} ) 5px 50% no-repeat; }

        .header-container { border-bottom:1px solid #415966; background:url( {$this->getimagesrc('bkg_header.jpg')} ) 50% 0 repeat-x; border-top:5px solid #0D2131;}
        .header { width:910px; margin:0 auto; padding:15px 10px 25px; text-align:left;}
        .header h1 { font-size:0; line-height:0; }

        .middle-container { background:#fbfaf6 url( {$this->getimagesrc('bkg_middle.gif')} ) 50% 0 no-repeat; }
        .middle { display:inline-block;width:900px; height:auto; margin:0 auto; background:#fffffe url( {$this->getimagesrc('bkg_middle2.gif')} ) 0 0 repeat-x; padding:25px 25px 80px 25px; text-align:left;}

        .middle[class] { height:auto; min-height:400px; }
        .side-col { width:195px; }
        .side-col li { zoom:1; }
        .side-col h2 {color:#0A263C;font-size:1.5em;margin-bottom:0.4em;}
        .side-col ul, ol {list-style-image:none;list-style-position:outside;list-style-type:none;}

        .col-left { float:left; }
        .col-main { float:left; }
        .col-2-left-layout .col-main { float:right; width:685px; }

        .fieldset { background:#fbfaf6; border:1px solid #bbafa0; margin:28px 0; padding:22px 25px 12px; }
        .fieldset .legend { background:#f9f3e3; border:1px solid #f19900; color:#e76200; float:left; font-size:1.1em; font-weight:bold; margin-top:-33px; padding:0 8px; position:relative; }
        .connection { float:left; }
        .connection,
        .connection .fieldset .legend {  border-color:#f16048; background:#ffffff; color:#df280a; }

        .footer-container { border-top:15px solid #b6d1e2; }
        .footer { width:930px; margin:0 auto; padding:10px 10px 4em; }
        .footer .legality { padding:13px 0; color:#ecf3f6; text-align:center; }
        .footer .legality a,
        .footer .legality a:hover { color:#ecf3f6; }

        li.failed { color:#ff0000; font-weight:bold; }

        #loading-mask { color:#d85909; font-size:1.1em; font-weight:bold; text-align:center; opacity:0.80; -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=80)"; z-index:500; }
        #loading-mask .loader { position:absolute; top:143px; left:50%; width:120px; margin-left:-70px; padding:15px 60px; background:#fff4e9; border:2px solid #f1af73; color:#d85909; font-weight:bold; text-align:center; z-index:1000; }

        </style>
        </head>
HEADER;
    }

    /**
     * Print body and logo.
     *
     * @param string $onload
     */
    public function printHtmlBodyTop($onload='')
    {
        echo <<<BODY
        <body onload="{$onload}">
            <div class="header-container">
                <div class="header">
                    <h1 title="Magento Downloader"><img src="{$this->getImageSrc('logo.gif')}" alt="Magento Downloader" /></h1>
                </div>
            </div>
BODY;
    }

    /**
     * Print closely body tag.
     */
    public function printHtmlBodyEnd()
    {
    echo <<<BODY
        </body>
    </html>
BODY;
    }

    /**
     * Print Footer HTML
     */
    public function printHtmlFooter()
    {
        $date = gmdate('Y');
        echo <<<FOOTER
        <div class="footer-container">
            <div class="footer">
                <p class="legality">Magento is a trademark of Magento, Inc. Copyright © {$date} Magento Inc.</p>
            </div>
        </div>
FOOTER;
    }

    /**
     * Print HTML form header
     */
    public function printHtmlFormHead()
    {
        echo <<<FORM
        <form action="" method="post" enctype="multipart/form-data" name="downloader_form" id="downloader_form">
FORM;
    }

    /**
     * Print HTML form footer
     */
    public function printHtmlFormFoot()
    {
        echo <<<FORM
        </form>
FORM;
    }

    /**
     * Print HTML container header
     */
    public function printHtmlContainerHead()
    {
        echo <<<HTML
        <div class="middle-container">
            <div class="middle col-2-left-layout">
HTML;
    }

    /**
     * Print HTML container footer
     */
    public function printHtmlContainerFoot()
    {
        echo <<<HTML
        </div>
        </div>
HTML;
    }

    /**
     * Print messages block
     *
     * @param array|string $messages
     * @param string $type
     */
    public function printHtmlMessage($messages, $type = 'error')
    {
        if (!is_array($messages)) {
            $messages = array($messages);
        }
        if (count($messages) == 0) {
            echo '';
            return;
        }
        $textMessages = '';
        foreach ($messages as $message) {
            $message = htmlspecialchars($message);
            $textMessages .= "<li>{$message}</li>";
        }
        echo <<<HTML
        <div class="messages">
            <ul class="msg_{$type}">
                {$textMessages}
            </ul>
        </div>
HTML;
    }

    /**
     * Print Page head block top
     *
     * @param string $title
     */
    public function printHtmlPageHeadTop($title)
    {
        $title = htmlspecialchars($title);
        echo <<<HTML
        <div class="col-main">
            <div class="page-head">
                <h3>{$title}</h3>
            </div>
HTML;
    }

    /**
     * Print Page head block end
     */
    public function printHtmlPageHeadEnd()
    {
        echo '</div>';
    }

    /**
     * Print buttons on page.
     *
     * @param array $buttons
     */
    public function printHtmlButtonSet($buttons)
    {
        $require = '';
        $textButtons = '';
        foreach ($buttons as $button => $label) {
            $textButtons .= '<button id="button-' . $button . '" class="button" type="submit" onclick="return buttonClick(\'' . $button . '\');">
                                <span>' . $label . '</span>
                             </button>';
        }
        echo <<<HTML
        <script type="text/javascript">
            function buttonClick(action)
            {
                document.getElementById('button-'+action).disabled = true;
                document.getElementById('downloader_form').action = '?action='+action;
                document.getElementById('downloader_form').submit();
                return false;
            }
        </script>
        <div class="button-set">
            {$require}
            {$textButtons}
        </div>
HTML;
    }

    /**
     * Retrieve POST data
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPost($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_POST;
        }
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        return $default;
    }

    /**
     * Print image content
     *
     * @param string $img
     */
    public function printImageContent($img)
    {
        if (isset($this->_images[$img])) {
            $imgProp = $this->_images[$img];
            header('Content-Type: ' . $imgProp['type']);
            echo base64_decode($imgProp['base64']);
        }
        else {
            header('HTTP/1.0 404 Not Found');
        }
    }

    /**
     * Retrieve Image URL for SRC
     *
     * @param string $image
     * @return string
     */
    public function getImageSrc($image)
    {
        return "{$_SERVER['PHP_SELF']}?img={$image}";
    }

    /**
     * Print left block with steps.
     *
     * @param string $activeStep
     */
    public function printHtmlLeftBlock($activeStep)
    {
        $steps = '';
        foreach($this->_steps as $_code => $_step) {
            $style = '';
            if ($activeStep == $_code) {
                $style = 'style="color:green; font-weight:bold;"';
            }
            $steps .= '<li ' . $style . '>' . $_step . '</li>';//style="color:green; font-weight:bold;
        }
        echo <<<HTML
        <div class="col-left side-col">
            <div style="border:1px solid #ccc; background:#f6f6f6;">
                <h2 style="margin-bottom:0; border-bottom:1px solid #ccc; padding:4px 10px; color:#3c5974; font-size:1.4em;">Installation</h2>
                <ol style="padding:10px; border-top:1px solid #fff;">
                    {$steps}
                </ol>
            </div>
            <br/>
            <p>
                Having trouble installing Magento?
                Check out our <a href="http://www.magentocommerce.com/install" id="installation_guide_link" target="installation_guide">Installation Guide</a>
            </p>
        </div>
HTML;
    }

    /**
     * Print Welcome Page.
     */
    public function printHtmlWelcomeBlock()
    {
        echo <<<HTML
<div>
        <p>This wizard will install Magento to your server. Please visit Magento community site
        <a href="http://www.magentocommerce.com/" target="_blank">http://www.magentocommerce.com/</a>  before you start to install.</p>
</div>
HTML;
    }

    /**
     * Print Validate page
     *
     * @param mixed $session
     */
    public function printHtmlValidateBlock($session)
    {
        $host     = isset($session['host']) ? $session['host'] : 'localhost';
        $username = isset($session['username']) ? $session['username'] : '';
        $database = isset($session['database']) ? $session['database'] : '';
        $password = !empty($session['password']) ? '******' : '';
        echo <<<HTML
        <div class="connection">
            <fieldset class="fieldset">
                <legend>Database Connection</legend>
                <div class="legend">Database Connection</div>
                <div class="input-box">
                    <label for="host">Host </label><br />
                    <input value="{$host}" type="text" name="host" id="host" class="input-text" />
                </div>
                <div class="input-box">
                    <label for="username">User Name </label><br />
                    <input value="{$username}" type="text" name="username" id="username" class="input-text" />
                </div>
                <div class="input-box">
                    <label for="password">User Password </label><br />
                    <input value="{$password}" type="password" name="password" id="password" class="input-text" />
                </div>
HTML;
                echo $this->printHtmlButtonSet(array('checkdb'=>'Check for InnoDB support'));
                echo <<<HTML
            </fieldset>
        </div>
HTML;
    }

    /**
     * Print deployment page
     *
     * @param array $params
     */
    public function printDeployBlock($params = array())
    {
        $fsDisabled = !Magento_Downloader_Worker::isCurrentFolderWritable();
        $fsDisabledHtml = ($fsDisabled) ? 'disabled="disabled"' : '';
        $fsChecked  = (isset($params['deployment']['type']) && $params['deployment']['type'] == 'fs')
            ? 'checked="checked"' : '';
        $ftpChecked = (isset($params['deployment']['type']) && $params['deployment']['type'] == 'ftp' || $fsDisabled)
            ? 'checked="checked"' : '';

        if (empty($fsChecked) && empty($fsChecked)) {
            $fsChecked = 'checked="checked"';
        }

        $ftpFormShow = empty($ftpChecked) ? 'style="display:none;"' : '';

        $ftpHost = (isset($params['deployment']['ftp_host'])) ? $params['deployment']['ftp_host'] : '';
        $ftpUser = (isset($params['deployment']['ftp_username'])) ? $params['deployment']['ftp_username'] : '';
        $ftpPswd = (isset($params['deployment']['ftp_password'])) ? $params['deployment']['ftp_password'] : '';
        $ftpPath = (isset($params['deployment']['ftp_path'])) ? $params['deployment']['ftp_path'] : '';

        $downloadHttpSelected = (isset($params['download_protocol']) && $params['download_protocol'] == 'http')
            ? ' selected="selected"' : '' ;
        $downloadFtpSelected = (isset($params['download_protocol']) && $params['download_protocol'] == 'ftp')
            ? ' selected="selected"' : '' ;

        echo <<<HTML
        <div>
            <fieldset class="fieldset">
                <legend>Loader protocol</legend>
                <div class="legend">Loader protocol</div>
                <div>
                    <div>
                        <div class="input-box">
                            <label for="download_protocol">Magento Connect Channel Protocol </label><br />
                            <select name="download_protocol" value="{$ftpPath}" id="download_protocol" class="input-text">
                                <option value="http"{$downloadHttpSelected}>HTTP</option>
                                <option value="ftp"{$downloadFtpSelected}>FTP</option>
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>
            <fieldset class="fieldset">
                <legend>Deployment Type</legend>
                <div class="legend">Deployment Type</div>
                <div>
                    <ul>
                        <li>
                            <input value="fs" type="radio" name="deployment_type" id="deployment_fs" onclick="switchMethod(this)" {$fsChecked} {$fsDisabledHtml} />
                            <span class="label">Local Filesystem</span>
                        </li>
                        <li>
                            <input value="ftp" type="radio" name="deployment_type" id="deployment_ftp" onclick="switchMethod(this)" {$ftpChecked} />
                            <span class="label">FTP Connection</span>
                        </li>
                    </ul>
                    <div id="ftp_authorize_form" {$ftpFormShow}>
                        <div class="input-box">
                            <label for="host">FTP Host </label><br />
                            <input type="text" name="ftp_host" value="{$ftpHost}" id="host" class="input-text" />
                        </div>
                        <div class="input-box">
                            <label for="username">FTP Login </label><br />
                            <input type="text" name="ftp_username" value="{$ftpUser}" id="username" class="input-text" />
                        </div>
                        <div class="input-box">
                            <label for="password">FTP Password </label><br />
                            <input type="password" name="ftp_password" value="{$ftpPswd}" id="password" class="input-text" />
                        </div>
                        <div class="input-box">
                            <label for="ftp_path">Installation Path </label><br />
                            <input type="text" name="ftp_path" value="{$ftpPath}" id="ftp_path" class="input-text" />
                        </div>
HTML;
        echo $this->printHtmlButtonSet(array('checkftp'=>'Check FTP connection'));
        echo <<<HTML
                    </div>
                </div>
            </fieldset>
        </div>
        <script>
            function switchMethod(method)
            {
                switch(method.value)
                {
                    case 'fs':
                      document.getElementById('ftp_authorize_form').style.display = 'none';
                      break;
                    case 'ftp':
                      document.getElementById('ftp_authorize_form').style.display = '';
                      break;
                }
            }
        </script>
HTML;
    }

    /**
     * Print authorization page
     *
     * @param array $params
     */
    public function printAuthorizationBlock($params = array())
    {
        $login = (isset($params['auth']['username'])) ? $params['auth']['username'] : '';
        $pswd = (isset($params['auth']['password'])) ? $params['auth']['password'] : '';
        echo <<<HTML
            <div>
                <fieldset class="fieldset">
                    <legend>Channel Server Credentials</legend>
                    <div class="legend">Channel Server Credentials</div>
                    <div class="input-box">
                        <label for="username">User Login </label><br />
                        <input type="text" name="auth_username" value="{$login}" id="username" class="input-text" />
                    </div>
                    <div class="input-box">
                        <label for="password">User Password </label><br />
                        <input type="password" name="auth_password" value="{$pswd}" id="password" class="input-text" />
                    </div>
HTML;
        echo $this->printHtmlButtonSet(array('check_auth' => 'Check Credentials'));
        echo <<<HTML
                </fieldset>
            </div>
HTML;
    }

    /**
     * Print Download page.
     */
    public function printHtmlDownloadBlock()
    {
        echo <<<HTML
            <script type="text/javascript">
                function download()
                {
                    document.getElementById('loading-mask').style.display = '';
                    var handler = false;
                    try {
                        handler = new XMLHttpRequest();
                    } catch (e) {
                        try {
                            handler = new ActiveXObject("Msxml2.XMLHTTP");
                        } catch (e) {
                            try {
                                handler = new ActiveXObject("Microsoft.XMLHTTP");
                            } catch (e) {
                                handler = false;
                            }
                        }
                    }
                    if (handler) {
                        handler.open("GET", "downloader.php?action=connect", true);
                        handler.onreadystatechange = function() {
                            if (handler.readyState==4) {
                                try {
                                    eval(handler.responseText);
                                } catch(e) {
                                    alert('Error: '+e.description);
                                }
                            }
                        }
                        handler.send(null);
                    }
                }

                function complete(message)
                {
                    document.getElementById('loading-mask').style.display = 'none';
                    document.getElementById('status').innerHTML = message;
                    document.getElementById('status').style.display = '';
                }
            </script>
            <div id="status" style="display:none;"></div>
            <div id="loading-mask" style="display:none">
                <p class="loader" id="loading_mask_loader"><img src="{$this->getimagesrc('ajax_loader_tr.gif')}" alt="Loading..."/><br/>Please wait...</p>
            </div>
HTML;
    }
}

class Magento_Downloader_Action
{
    /**
     * Helper object
     *
     * @var Magento_Downloader_Helper
     */
    protected $_helper;

    /**
     * Validator object
     *
     * @var Magento_Downloader_Validator
     */
    protected $_validator;

    /**
     * Session array
     *
     * @var array
     */
    protected $_session;

    /**
     * Worker object
     *
     * @var Magento_Downloader_Worker
     */
    protected $_worker;

    /**
     * Init class
     */
    public function __construct()
    {
        if (!isset($_SESSION)) {
            session_name('magento_downloader_session');
            session_start();
        }
        $this->_helper    = new Magento_Downloader_Helper();
        $this->_worker    = new Magento_Downloader_Worker();
        $this->_validator = new Magento_Downloader_Validator();
        $this->_session   = &$_SESSION;
    }

    /**
     * Retrieve validator object
     *
     * @return Magento_Downloader_Validator
     */
    public function getValidator()
    {
        return $this->_validator;
    }

    /**
     * Images
     *
     * @return Magento_Downloader_Action
     */
    public function imageAction()
    {
        $this->_helper->printImageContent($_GET['img']);
        return $this;
    }

    /**
     * Show welcome page
     *
     * @return Magento_Downloader_Action
     */
    public function welcomeAction()
    {
        $this->_helper->printHtmlHeader();
        $this->_helper->printHtmlBodyTop();
        $this->_helper->printHtmlFormHead();
        $this->_helper->printHtmlContainerHead();
        $this->_helper->printHtmlLeftBlock('welcome');
        $this->_helper->printHtmlPageHeadTop('Welcome to Magento Downloader!');
        if (isset($this->_session['errors'])) {
            $this->_helper->printHtmlMessage($this->_session['errors'], 'error');
            unset($this->_session['errors']);
        }
        $this->_helper->printHtmlWelcomeBlock();
        $this->_helper->printHtmlPageHeadEnd();
        $this->_helper->printHtmlButtonSet(array('validate' => 'Continue'));
        $this->_helper->printHtmlContainerFoot();
        $this->_helper->printHtmlFormFoot();
        $this->_helper->printHtmlFooter();
        $this->_helper->printHtmlBodyEnd();
        return $this;
    }

    /**
     * Show validate page
     *
     * @return Magento_Downloader_Action
     */
    public function validateAction()
    {
        $this->getValidator()->validatePhp();
        $this->getValidator()->validatePermissions();
        $errors = $this->getValidator()->getErrors();

        if (isset($_GET['action']) && $_GET['action'] == 'checkdb') {
            $this->_session['host'] = $this->_helper->getPost('host');
            $this->_session['username'] = $this->_helper->getPost('username');
            $this->_session['database'] = $this->_helper->getPost('database');
            if ($this->_helper->getPost('password') != '******') {
                $this->_session['password'] = $this->_helper->getPost('password');
            }
            $this->getValidator()->validateDb(
                $this->_session['host'],
                $this->_session['username'],
                $this->_session['password'],
                $this->_session['database']);
        }

        $buttons = array(
            'welcome'  => 'Back',
            'validate' => 'Check Again',
            'deploy'   => 'Continue'
        );

        $messages = $this->getValidator()->getMessages();
        $dbErrors = $this->getValidator()->getErrors();
        $this->_helper->printHtmlHeader();
        $this->_helper->printHtmlBodyTop();
        $this->_helper->printHtmlFormHead();
        $this->_helper->printHtmlContainerHead();
        $this->_helper->printHtmlLeftBlock('validate');
        $this->_helper->printHtmlPageHeadTop('Validation for Magento Downloader.');
        $this->_helper->printHtmlMessage($messages, 'success');
        $this->_helper->printHtmlMessage($errors);
        $this->_helper->printHtmlMessage($dbErrors);
        $this->_helper->printHtmlValidateBlock($this->_session);
        $this->_helper->printHtmlButtonSet($buttons);
        $this->_helper->printHtmlPageHeadEnd();
        $this->_helper->printHtmlContainerFoot();
        $this->_helper->printHtmlFormFoot();
        $this->_helper->printHtmlFooter();
        $this->_helper->printHtmlBodyEnd();
        return $this;
    }

    /**
     * Deploy magento connect manager action
     *
     * @return Magento_Downloader_Action
     */
    public function deployAction()
    {
        $ftpChecked=false;
        $deploymentType = $this->_helper->getPost('deployment_type');

        if (isset($deploymentType) && !empty($deploymentType)) {
            $this->_session['deployment']['type'] = $deploymentType;
            $this->_session['download_protocol'] = $this->_helper->getPost('download_protocol', 'http');
            if ($deploymentType == 'ftp') {
                $this->_session['deployment']['ftp_host']       = $this->_helper->getPost('ftp_host', '');
                $this->_session['deployment']['ftp_username']   = $this->_helper->getPost('ftp_username', '');
                $this->_session['deployment']['ftp_password']   = $this->_helper->getPost('ftp_password', '');
                $this->_session['deployment']['ftp_path']       = $this->_helper->getPost('ftp_path', '');
            }
        }

        if (isset($_GET['action']) && $_GET['action'] == 'checkftp' || (isset($deploymentType) && $deploymentType == 'ftp')) {
            $this->_session['deployment']['type'] = ($deploymentType) ? $deploymentType : 'fs';
            $ftpServer = $this->_session['deployment']['ftp_host'] = $this->_helper->getPost('ftp_host', '');
            $ftpUser = $this->_session['deployment']['ftp_username'] = $this->_helper->getPost('ftp_username', '');
            $ftpPass = $this->_session['deployment']['ftp_password'] = $this->_helper->getPost('ftp_password', '');
            $ftpPath = $this->_session['deployment']['ftp_path'] = $this->_helper->getPost('ftp_path', '');
            $this->_session['download_protocol'] = $this->_helper->getPost('download_protocol', 'http');

            $connId = @ftp_connect($ftpServer);

            if ($connId) {
                if (@ftp_login($connId, $ftpUser, $ftpPass)) {
                    @ftp_pasv($connId, true);
                    $this->getValidator()->addMessage("Successfully connected as $ftpUser on $ftpServer\n");
                    $ftpChecked=true;
                    if (!empty($ftpPath)) {
                        if (!@ftp_chdir($connId, $ftpPath)) {
                            $this->getValidator()->addError("Couldn't retrieve installation directory");
                            $ftpChecked=false;
                        }
                    }
                } else {
                    $this->getValidator()->addError("Could not connect as $ftpUser on $ftpServer\n");
                }
                ftp_close($connId);
            } else {
                $this->getValidator()->addError("Could not connect to your \"$ftpServer\" FTP Host. Please enter valid data to Deploymetn Type fields.");
            }
        }

        if (isset($deploymentType) && $_GET['action'] != 'checkftp' && ($deploymentType=='ftp' && $ftpChecked || $deploymentType=='fs')) {
            header("Location: ?action=authorize");
            die;
        }

        $buttons = array(
            'validate'  => 'Back',
            'deploy'    => 'Continue'
        );

        $this->_helper->printHtmlHeader();
        $this->_helper->printHtmlBodyTop();
        $this->_helper->printHtmlFormHead();
        $this->_helper->printHtmlContainerHead();
        $this->_helper->printHtmlLeftBlock('deploy');
        $this->_helper->printHtmlPageHeadTop('Magento Connect Manager Deployment');
        $this->_helper->printHtmlMessage($this->getValidator()->getMessages(), 'success');
        $this->_helper->printHtmlMessage($this->getValidator()->getErrors());
        $this->_helper->printDeployBlock($this->_session);
        $this->_helper->printHtmlButtonSet($buttons);
        $this->_helper->printHtmlPageHeadEnd();
        $this->_helper->printHtmlContainerFoot();
        $this->_helper->printHtmlFormFoot();
        $this->_helper->printHtmlFooter();
        $this->_helper->printHtmlBodyEnd();
        return $this;
    }

    /**
     * Authorize action
     *
     * @return Magento_Downloader_Action
     */
    public function authorizeAction()
    {
        $userName = $this->_helper->getPost('auth_username','null');
        $userPassword = $this->_helper->getPost('auth_password','null');
        if('null'==$userName||'null'==$userPassword){
            $userName='';
            $userPassword='';
        }elseif ($userName && $userPassword) {
            $this->_session['auth']['username'] = $userName;
            $this->_session['auth']['password'] = $userPassword;
        }else{
            $this->getValidator()->addError("Please enter User Login and Password.");
        }

        if (isset($_GET['action']) && $_GET['action'] == 'check_auth' && $userName && $userPassword) {

            if ($this->_session['download_protocol'] == 'ftp') {
                $auth = $this->_worker->ftpAuthorize(
                    $userName,
                    $userPassword
                );
            } else {
                $auth = $this->_worker->authorize(
                    $userName,
                    $userPassword
                );
            }
            if (is_bool($auth)) {
                if ($auth) {
                    $this->getValidator()->addMessage("Successfully authorized as {$userName}.");
                } else {
                    $this->getValidator()->addError("Failed to authorize as {$userName}.");
                }
            } else {
                $this->getValidator()->addError('Could not connect to server.');
            }

        }

        if ($_GET['action'] != 'check_auth' && $userName && $userPassword) {
            header("Location: ?action=download");
            die;
        }

        $buttons = array(
            'deploy'    => 'Back',
            'authorize' => 'Continue'
        );

        $this->_helper->printHtmlHeader();
        $this->_helper->printHtmlBodyTop();
        $this->_helper->printHtmlFormHead();
        $this->_helper->printHtmlContainerHead();
        $this->_helper->printHtmlLeftBlock('authorize');
        $this->_helper->printHtmlPageHeadTop('Channel Server Authorization');
        $this->_helper->printHtmlMessage($this->getValidator()->getErrors());
        $this->_helper->printHtmlMessage($this->getValidator()->getMessages(), 'success');
        $this->_helper->printAuthorizationBlock($this->_session);
        $this->_helper->printHtmlButtonSet($buttons);
        $this->_helper->printHtmlPageHeadEnd();
        $this->_helper->printHtmlContainerFoot();
        $this->_helper->printHtmlFormFoot();
        $this->_helper->printHtmlFooter();
        $this->_helper->printHtmlBodyEnd();
        return $this;
    }

    /**
     * Show download page.
     *
     * @return Magento_Downloader_Action
     */
    public function downloadAction()
    {
        $buttons = array(
            'authorize' => 'Back',
            'downloader' => 'Continue'
        );

        $this->_helper->printHtmlHeader();
        $this->_helper->printHtmlBodyTop('download()');
        $this->_helper->printHtmlFormHead();
        $this->_helper->printHtmlContainerHead();
        $this->_helper->printHtmlLeftBlock('download');
        $this->_helper->printHtmlPageHeadTop('Downloading');
        $this->_helper->printHtmlDownloadBlock();
        $this->_helper->printHtmlButtonSet($buttons);
        $this->_helper->printHtmlPageHeadEnd();
        $this->_helper->printHtmlContainerFoot();
        $this->_helper->printHtmlFormFoot();
        $this->_helper->printHtmlFooter();
        $this->_helper->printHtmlBodyEnd();
    }

    /**
     * AJAX action for download magento.
     */
    public function connectAction()
    {
        if (!isset($this->_session['downloaded']) || !$this->_session['downloaded']) {
            try {
                $worker = $this->_worker;
                if ($this->_session['download_protocol'] == 'ftp') {
                    $worker->ftpDownload();
                } else {
                    $worker->download();
                }
                if ($this->_session['deployment']['type'] == 'ftp') {
                    $worker->unpack(true);
                    $worker->ftpCopy($this->_session['deployment']);
                } else {
                    $worker->unpack();
                }
                $msg = 'Magento has been downloaded successfully.';
                $this->_session['downloaded'] = true;
            } catch (Exception $e) {
                $msg = addslashes($e->getMessage());
                $msg = $e->getMessage();
                echo <<<SCRIPT
                document.getElementById('button-downloader').disabled = true;
                document.getElementById('button-downloader').setAttribute('class', 'button_disabled');
                alert('{$msg}\\nTry Again (refresh page)');
                complete('Downloading Failed.');
SCRIPT;
                return $this;
            }
        } else {
            $msg = 'Magento has been downloaded earlier.';
        }

        echo <<<SCRIPT
        complete('{$msg}');
SCRIPT;
    }

    /**
     * Run action
     *
     * @return Magento_Downloader_Action
     */
    public function run()
    {
        if (isset($_GET['img'])) {
            return $this->imageAction();
        }
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'checkdb':
                case 'validate':
                    $this->validateAction();
                    break;
                case 'deploy':
                case 'checkftp':
                    $this->deployAction();
                    break;
                case 'check_auth':
                case 'authorize':
                    $this->authorizeAction();
                    break;
                case 'download':
                    $this->downloadAction();
                    break;
                case 'connect':
                    $this->connectAction();
                    break;
                case 'downloader':
                    header('Location: index.php');
                    break;
                default:
                    $this->welcomeAction();
            }
        } else {
            $this->welcomeAction();
        }
        return $this;
    }
}

@set_time_limit(0);

if (!is_writable(session_save_path()) && !is_writable(sys_get_temp_dir())) {
    throw new Exception("Unable to save session data.");
}
session_save_path(sys_get_temp_dir());

$downloader = new Magento_Downloader_Action();
$downloader->run();
