<?php
/**
 * Read configuration file and rewrite.
 *
 * @filesource crond.php
 * @package maguire
 * @subpackage crond
 * @version $id: 0.1, utf8, Wed Jan  5 14:13:45 CST 2011$
 * @author Bobby4k <bobby4kit[at]outlook.com>
 *
 */
global $CONFIG_INI, $CONFIG_ARR, $CONFIG_CONTENT;
global $SYS_CONFIGARR;
$SYS_CONFIGARR['workspace'] = dirname(__FILE__);
$SYS_CONFIGARR['lockdir']   = dirname(__FILE__) . DIRECTORY_SEPARATOR .'lock';
@mkdir($SYS_CONFIGARR['lockdir']);


if (!empty($argv[1])) {
    $tmp_file = pathinfo(str_replace(' ', '', $argv[1]), PATHINFO_FILENAME);
    $CONFIG_INI = $SYS_CONFIGARR['workspace'] . DIRECTORY_SEPARATOR . $tmp_file . '.ini';
} else {
    //default
    $CONFIG_INI = $SYS_CONFIGARR['workspace'] . DIRECTORY_SEPARATOR . 'crond.ini';
}

//file exists
if (!is_file($CONFIG_INI)){
    echo "\n\tFile:{$CONFIG_INI} does not exist. Please check.\n\n";
    return;
}
$m = null;
preg_match('/\/(?P<crond_name>\w+)\.ini$/', $CONFIG_INI, $m);
$crond_name = $m['crond_name'];

//parse ini
$CONFIG_ARR = parse_ini_file($CONFIG_INI, true);
$CONFIG_CONTENT = '';
$CONFIG_SHELL_CONTENT = "#!/bin/bash\n";
$DEFAULT_TIME = 180; //默认轮转时间 180s.

if (empty($CONFIG_ARR)) {
    exit('empty ini, bye!');
}

//循环 check 每一个配置
foreach ($CONFIG_ARR as $k => $v) {

    if($k=='system'){
        $CONFIG_CONTENT .= "[{$k}]\n; System configuration\n";
        //system configure
        foreach(['workspace','lockdir'] as $sk){
            if ( array_key_exists($sk,$v) ){
                if (strlen($v[$sk])>3){
                    //mkdir($v[$sk]); //try mkdir 0777
                    if( !is_dir($v[$sk]) ){
                        echo "\n\tERR: The parameter '{$sk}' is not a directory.\n\n";
                        return;
                    }
                    if ( 'lockdir'==$sk and !is_writable($v[$sk]) ){
                        echo "\n\tERR: The directory '{$sk}' is not writable.\n\n";
                        return;
                    }
                    $SYS_CONFIGARR[$sk] = $v[$sk];
                }
            }
            echo "\tcurrent system param {$sk}: {$SYS_CONFIGARR[$sk]}\n";
            $CONFIG_CONTENT .= "{$sk} = \"{$SYS_CONFIGARR[$sk]}\"\n";
        }
        $CONFIG_CONTENT .= "\n\n";
        continue;//next app configure
    }

    $CONFIG_CONTENT .= "[{$k}]\n; Command in workspace\n";
    $CONFIG_CONTENT .= "command = \"{$v['command']}\"\n";

    $workspace = $SYS_CONFIGARR['workspace'];
    if (!empty($v['workspace']) && is_dir($v['workspace']) ) {
        $workspace = $v['workspace'];
        $CONFIG_CONTENT .= "; Task Workspace \nworkspace = \"{$workspace}\"\n";
    }

    if (!empty($v['time']) && is_numeric($v['time'])) {
        $v['time'] = (int)$v['time'];
    } else {
        $v['time'] = $DEFAULT_TIME;
    }
    $CONFIG_CONTENT .= "; in seconds\ntime = {$v['time']}\n";

    if ('on' != $v['enable']) {
        $v['enable'] = 'off';
    }
    $CONFIG_CONTENT .= "; Is the task enabled: on/off\nenable = \"{$v['enable']}\"\n";

    $v['lock'] = crond_encode($v['command']);
    $CONFIG_CONTENT .= "; lock file\nlock = \"{$v['lock']}\"\n";

    if (!empty($v['desc'])) {
        $v['desc'] = strtr($v['desc'], '"', '”');
        $CONFIG_CONTENT .= "; Description of this task\ndesc = \"{$v['desc']}\"\n";
    }
    $CONFIG_CONTENT .= "\n\n";

    if (count($argv)>2 and $argv[2]=='pkill'){
        $CONFIG_SHELL_CONTENT .= "pkill -f \"{$v['command']}\"\n";
    }else
        $CONFIG_SHELL_CONTENT .= "nohup sh run.sh {$v['lock']} {$v['time']} {$v['enable']} \"{$workspace}\"> /dev/null 2>&1 &\n";
}
//END for
file_put_contents($CONFIG_INI, $CONFIG_CONTENT);
$CONFIG_SH  = $SYS_CONFIGARR['lockdir'] . DIRECTORY_SEPARATOR . "{$crond_name}.sh";
file_put_contents($CONFIG_SH,  $CONFIG_SHELL_CONTENT);

echo "\n\tAlong Crond Command execution with {$CONFIG_SH} \n\sn";
exec("sh {$CONFIG_SH}");

// 采用64位加密
function crond_encode($str)
{
    return strtr(base64_encode($str), '+/=', '-_~');
}

//解密 base64
function crond_decode($str)
{
    return base64_decode(strtr($str, '-_~', '+/='));
}