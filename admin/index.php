<?php

include 'admin_header.php';
//error_reporting (E_ALL);
if (!isset($action)) {
    $action = 'main';
}
/*
Copyright (C) 2002 Edwin van Wijk, webftp@v-wijk.net

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

$downloadDir = '/tmp/';
$unzipCommand = "sudo -u $user /usr/bin/unzip";
$port = 22;

function del_recursive($currentDir, $connection, $file)
{
    //echo "entering $currentDir/$file<br>";

    if ($lista = @ftp_nlist($connection, (string)$file)) {
        for ($x = 0, $xMax = count($lista); $x < $xMax; $x++) {
            //echo "tryng to delete $lista[$x]<br>";

            if (!@ftp_delete($connection, (string)$lista[$x])) {
                del_recursive($currentDir, $connection, $lista[$x]);
            }
        }

        @ftp_rmdir($connection, (string)$file);
    }
}

include 'parser.inc.php';

session_start();

$HPV = $_POST;

// Get the POST, GET and SESSION variables (if register_globals=off (PHP4.2.1+))
// It's a bit of a dirty hack but variables are sometimes GET and sometimes POST variables
$mode = $HPV['mode'] ?? $_GET['mode'];
$action = $HPV['action'] ?? $_GET['action'];
$currentDir = $HPV['currentDir'] ?? $_GET['currentDir'];
$file = $HPV['file'] ?? $_GET['file'];
$file2 = $HPV['file2'] ?? $_GET['file2'];
$permissions = $HPV['permissions'] ?? $_GET['permissions'];
$directory = $HPV['directory'] ?? $_GET['directory'];
$MAX_FILE_SIZE = $HPV['MAX_FILE_SIZE'] ?? $_GET['MAX_FILE_SIZE'];
$logoff = $HPV['logoff'] ?? $_GET['logoff'];

if (isset($HTTP_SESSION_VARS['server'])) {
    $server = $HTTP_SESSION_VARS['server'];

    $user = $HTTP_SESSION_VARS['user'];

    $password = $HTTP_SESSION_VARS['password'];

    $port = $HTTP_SESSION_VARS['port'];
} else {
    $server = $_POST['server'];

    $user = $_POST['user'];

    $password = $_POST['password'];

    $port = $_POST['port'];
}

if (isset($logoff)) {
    session_unregister('server');

    session_unregister('user');

    session_unregister('password');

    session_unregister('port');

    unset($server);

    unset($user);

    unset($password);

    unset($port);

    session_destroy();
}

if (isset($server)) {
    session_register('server', $server);

    session_register('user', $user);

    session_register('password', $password);

    session_register('port', $port);

    //		$connection = @ftp_connect($server);

    $connection = @ftp_connect($server, $port);

    $loggedOn = @ftp_login($connection, $user, $password);

    $systype = @ftp_systype($connection);

    if (!isset($mode)) {
        $mode = 1; //(FTP_ASCII = 0; FTP_BINARY=1)
    }

    if ($loggedOn) {
        if (isset($currentDir)) {
            ftp_chdir($connection, $currentDir);
        }

        $currentDir = ftp_pwd($connection);

        $msg = "Current directory = $currentDir";

        // what to do now ???

        if (isset($action)) {
            switch ($action) {
                case 'chmod':    // Change permissions
                    if (@ftp_site($connection, "chmod $permissions $file")) {
                        $msg = 'File permission changed.';
                    } else {
                        $msg = 'Could not change permissions for ' . $file;
                    }
                    break;
                case 'cd':            // Change directory
                    //First try : normal directory
                    if (@ftp_chdir($connection, $currentDir . '/' . $file)) {
                        $currentDir = @ftp_pwd($connection);

                        $msg = 'Current directory = ' . $currentDir;
                    } elseif (@ftp_chdir($connection, $file)) { // Symbolic link directory
                        $currentDir = @ftp_pwd($connection);

                        $msg = 'Current directory = ' . $currentDir;
                    } else { // link to a file so let's retrieve this...
                        header("Content-disposition: attachment; filename=\"$file\"");

                        header('Content-type: application/octetstream');

                        header('Pragma: ');

                        header('Cache-Control: cache');

                        header('Expires: 0');

                        //Determine original filename

                        $filearray = explode('/', $file);

                        $file = $filearray[count($filearray) - 1];

                        $msg = $file;

                        $fp = fopen($downloadDir . $file, 'wb');

                        if (!@ftp_fget($connection, $fp, (string)$file, $mode)) {
                            fclose($fp);

                            exit;
                        }

                        fclose($fp);

                        $data = readfile($downloadDir . $file);

                        $i = 0;

                        while ('' != $data[$i]) {
                            echo $data[$i];

                            $i++;
                        }

                        unlink($downloadDir . $file);

                        exit;
                    }
                    break;
                case 'get':            // Download file
                    header("Content-disposition: attachment; filename=\"$file\"");
                    header('Content-type: application/octetstream');
                    header('Pragma: ');
                    header('Cache-Control: cache');
                    header('Expires: 0');

                    $fp = fopen($downloadDir . $file, 'wb');
                    ftp_fget($connection, $fp, (string)$file, $mode) || die('Error downloading file');
                    fclose($fp);
                    $data = readfile($downloadDir . $file);
                    $i = 0;
                    while ('' != $data[$i]) {
                        echo $data[$i];

                        $i++;
                    }
                    unlink($downloadDir . $file);
                    exit;
                    break;
                case 'put':            // Upload file
                    if ($file_size > $MAX_FILE_SIZE) {
                        $msg = '<BFile size too big!</B> (max. ' . $MAX_FILE_SIZE . 'bytes)<P>';
                    } else {
                        if (file_exists($HTTP_POST_FILES['file']['tmp_name'])) {
                            if (1 == $mode) {
                                ftp_put($connection, $currentDir . '/' . $HTTP_POST_FILES['file']['name'], $HTTP_POST_FILES['file']['tmp_name'], 1);
                            } else {
                                ftp_put($connection, $currentDir . '/' . $HTTP_POST_FILES['file']['name'], $HTTP_POST_FILES['file']['tmp_name'], 0);
                            }

                            unlink($HTTP_POST_FILES['file']['tmp_name']);
                        } else {
                            $msg = 'File could not be uploaded.';
                        }
                    }
                    break;
                case 'deldir':        // Delete directory
                    if (@ftp_rmdir($connection, (string)$file)) {
                        $msg = "$file deleted";
                    } else {
                        //Verify if has files inside and if so, call recursive del

                        if ($lista = @ftp_nlist($connection, "$currentDir/$file")) {
                            del_recursive($currentDir, $connection, $file);

                            $msg = "Directory $currentDir/$file deleted";
                        } else {
                            $msg = "Could not delete $file";
                        }
                    }
                    break;
                case 'delfile':        // Delete file
                    if (@ftp_delete($connection, (string)$file)) {
                        $msg = "$file deleted";
                    } else {
                        $msg = "Could not delete $file";
                    }
                    break;
                case 'rename':        // Rename file
                    if (@ftp_rename($connection, (string)$file, (string)$file2)) {
                        $msg = "$file renamed to $file2";
                    } else {
                        $msg = "Could not rename $file to $file2";
                    }
                    break;
                case 'createdir':  // Create a new directory
                    if (@ftp_mkdir($connection, (string)$file)) {
                        $msg = "$file created";
                    } else {
                        $msg = "Could not create $file";
                    }
                    break;
                case 'unzipfile':
                    $filens = str_replace(' ', '\\ ', $file);

                    if (exec("$unzipCommand $currentDir/$filens -d $currentDir/")) {
                        $msg = "$file unziped";
                    } else {
                        $msg = 'Could not unzip the file';
                    }
                    break;
            }
        } ?>
        <HTML>
        <HEAD>
            <LINK REL=StyleSheet HREF="style/cm.css" TITLE=Contemporary TYPE="text/css">
            <SCRIPT LANGUAGE="JavaScript" SRC="include/script.js"></SCRIPT>
        </HEAD>
        <BODY>
        <TABLE BORDER=0 CELLPADDING=2 CELLSPACING=0 WIDTH='100%'>
            <TR>
                <TD CLASS=menu>
                    <?php if ($loggedOn) { ?>
                        [&nbsp;&nbsp;<A CLASS=menu HREF="<?= $PHP_SELF; ?>?logoff=true">Log off</A>&nbsp;&nbsp;
                                                                                                   |&nbsp;&nbsp;<A CLASS=menu HREF="javascript:changeMode('0')">ASCII Mode</A>&nbsp;&nbsp;
                                                                                                                                                                              |&nbsp;&nbsp;<A CLASS=menu HREF="javascript:changeMode('1')">Binary Mode</A>&nbsp;&nbsp;
                        ]
                    <?php } else { ?>
                        [&nbsp;&nbsp;<A CLASS=menu HREF="<?= $PHP_SELF; ?>?logoff=true">Retry</A>&nbsp;&nbsp;]
                    <?php } ?>
                </TD>
                <TD CLASS=menu ALIGN=RIGHT>
                    <FORM METHOD=POST NAME="currentMode">
                        Current MODE :<INPUT TYPE='text' NAME='showmode' VALUE='<?= 1 == $mode ? 'FTP_BINARY' : 'FTP_ASCII'; ?>' STYLE='border: none; background-color: #cfcfbb; text-align: right; size:200px;' ALIGN=RIGHT>
                </TD>
                </FORM>
            </TR>
            <TR>
                <TD><?= $msg; ?></TD>
                <TD ALIGN=RIGHT><?php print ($loggedOn) ? "Connected to $server:$port ($systype)" : 'Not connected'; ?></TD>
            </TR>
        </TABLE>

        <FORM NAME="actionform" METHOD=POST ACTION='<?= $PHP_SELF; ?>'>
            <INPUT TYPE='hidden' NAME='action' VALUE=''>
            <INPUT TYPE='hidden' NAME='currentDir' VALUE='<?= $currentDir; ?>'>
            <INPUT TYPE='hidden' NAME='file' VALUE=''>
            <INPUT TYPE='hidden' NAME='file2' VALUE=''>
            <INPUT TYPE='hidden' NAME='permissions' VALUE=''>
            <INPUT TYPE='hidden' NAME='mode' VALUE='<?= $mode; ?>' STYLE='border: none; background-color: #EFEFEF;'>
        </FORM>
        <HR>

        <TABLE CELLPADDING=2 CELLSPACING=0>
            <TR>
                <!-- Goto directory -->
                <FORM NAME='cdDirect' METHOD=POST ACTION='<?= $PHP_SELF; ?>'>
                    <INPUT TYPE='hidden' NAME='action' VALUE='cd'>
                    <INPUT TYPE='hidden' NAME='currentDir' VALUE='<?= $currentDir; ?>'>
                    <TD VALIGN=TOP>
                        <INPUT TYPE="text" NAME="file" VALUE="">
                    </TD>
                    <TD VALIGN=TOP>
                        <INPUT TYPE="SUBMIT" VALUE="Go to Directory" STYLE='width=120;'>
                    </TD>
                </FORM>
            </TR>
            <TR>
                <!-- Create directory -->
                <FORM METHOD=POST NAME='dirinput' ACTION="<?= $PHP_SELF; ?>">
                    <TD VALIGN=TOP>
                        <INPUT TYPE="text" NAME="directory" VALUE="">
                    </TD>
                    <TD VALIGN=TOP>
                        <INPUT TYPE="BUTTON" VALUE="Create Directory" OnClick='javascript:createDirectory(dirinput.directory.value)' STYLE='width=120;'>
                    </TD>
                </FORM>
            </TR>
            <TR>
                <FORM NAME='putForm' ENCTYPE="multipart/form-data" METHOD=POST ACTION="<?= $PHP_SELF; ?>">
                    <INPUT TYPE="hidden" NAME="action" VALUE="put">
                    <INPUT TYPE='hidden' NAME='currentDir' VALUE='<?= $currentDir; ?>'>
                    <INPUT TYPE="hidden" NAME="MAX_FILE_SIZE" VALUE="2000000">
                    <INPUT TYPE='hidden' NAME='mode' VALUE='<?= $mode; ?>'>
                    <TD VALIGN=TOP>
                        <INPUT TYPE="file" NAME="file" STYLE="width:250px;">
                    </TD>
                    <TD VALIGN=TOP>
                        <INPUT TYPE="SUBMIT" VALUE="Upload file" STYLE='width=120;'>
                    </TD>
                </FORM>
            </TR>
        </TABLE>
        <HR>
        <P>
        <?php
        $list = [];

        $list = ftp_rawlist($connection, ''); ?>
        <TABLE>
        <TR>
            <TD><IMG SRC="../img/parent.gif" HEIGHT=20 WIDTH=20 ALIGN=TOP></TD>
            <TD ALIGN=LEFT COLSPAN=7><A HREF='javascript:submitForm("cd","..")'>..</A></TD>
        </TR>
        <?php
        $list = parse_ftp_rawlist($list, $systype);

        if (is_array($list)) {
            // Directories

            foreach ($list as $myDir) {
                if (1 == $myDir['is_dir']) {
                    $fileAction = 'cd';

                    $fileName = $myDir['name'];

                    print "<TR>\n";

                    print "<TD><IMG SRC=../img/folder.gif ALIGN=TOP></TD>\n";

                    print "<TD><A HREF='javascript:submitForm(\"cd\",\"" . $fileName . "\")'>" . $myDir['name'] . "</A></TD>\n";

                    print '<TD ALIGN=RIGHT>' . $myDir['size'] . "</TD>\n";

                    print '<TD>' . $myDir['date'] . "</TD>\n";

                    print '<TD>' . $myDir['perms'] . "</TD>\n";

                    print '<TD>' . $myDir['user'] . "</TD>\n";

                    print '<TD>' . $myDir['group'] . "</TD>\n";

                    print "<TD><A HREF='javascript:Confirmation(\"" . $PHP_SELF . '?action=deldir&file=' . $myDir['name'] . '&currentDir=' . $currentDir . "\")'><IMG SRC=../img/delete.gif BORDER=0 ALT=\"Delete\"></A></TD>\n";

                    print "<TD><A HREF='javascript:renameFile(\"" . $myDir['name'] . "\")'><IMG SRC=../img/rename.gif BORDER=0 ALT=\"Rename\"></A></TD>\n";

                    print '<TD>';

                    print "<A HREF='javascript:;' OnClick='window.open(\"setpermission.php?file="
                          . $fileName
                          . '&perms='
                          . $myDir['perms']
                          . "\",\"permissions\",\"width=250,height=150,scrollbars=no,menubar=no,status=yes,directories=no,location=no\")'><IMG SRC='../img/settings.gif' WIDTH='20' HEIGHT='20' BORDER=0 ALT='Change permissions'></A>";

                    print "</TD>\n";

                    print "</TR>\n";
                }
            }

            // Links

            foreach ($list as $myDir) {
                if (1 == $myDir['is_link']) {
                    $fileAction = 'cd';

                    $fileName = $myDir['target'];

                    print "<TR>\n";

                    print "<TD><IMG SRC=../img/link.gif ALIGN=TOP></TD>\n";

                    print "<TD><A HREF='javascript:submitForm(\"cd\",\"" . $fileName . "\")'>" . $myDir['name'] . "</A></TD>\n";

                    print '<TD ALIGN=RIGHT>' . $myDir['size'] . "</TD>\n";

                    print '<TD>' . $myDir['date'] . "</TD>\n";

                    print '<TD>' . $myDir['perms'] . "</TD>\n";

                    print '<TD>' . $myDir['user'] . "</TD>\n";

                    print '<TD>' . $myDir['group'] . "</TD>\n";

                    print "<TD><A HREF='javascript:Confirmation(\"" . $PHP_SELF . '?action=deldir&file=' . $myDir['name'] . '&currentDir=' . $currentDir . "\")'><IMG SRC=../img/delete.gif BORDER=0 ALT=\"Delete\"></A></TD>\n";

                    print "<TD><A HREF='javascript:renameFile(\"" . $myDir['name'] . "\")'><IMG SRC=../img/rename.gif BORDER=0 ALT=\"Rename\"></A></TD>\n";

                    print '<TD>';

                    print 'Symbolic link to ' . $myDir['target'];

                    print "</TD>\n";

                    print "</TR>\n";
                }
            }

            // Files

            foreach ($list as $myDir) {
                if (1 != $myDir['is_link'] && 1 != $myDir['is_dir']) {
                    $fileAction = 'get';

                    $fileName = $myDir['name'];

                    print "<TR>\n";

                    print "<TD><IMG SRC=../img/file.gif ALIGN=TOP></TD>\n";

                    print "<TD><A HREF='javascript:submitForm(\"get\",\"" . $fileName . "\")'>" . $myDir['name'] . "</A></TD>\n";

                    print '<TD ALIGN=RIGHT>' . $myDir['size'] . "</TD>\n";

                    print '<TD>' . $myDir['date'] . "</TD>\n";

                    print '<TD>' . $myDir['perms'] . "</TD>\n";

                    print '<TD>' . $myDir['user'] . "</TD>\n";

                    print '<TD>' . $myDir['group'] . "</TD>\n";

                    print "<TD><A HREF='javascript:Confirmation(\"" . $PHP_SELF . '?action=delfile&file=' . $myDir['name'] . '&currentDir=' . $currentDir . "\")'><IMG SRC=../img/delete.gif BORDER=0 ALT=\"Delete\"></A></TD>\n";

                    print "<TD><A HREF='javascript:renameFile(\"" . $myDir['name'] . "\")'><IMG SRC=../img/rename.gif BORDER=0 ALT=\"Rename\"></A></TD>\n";

                    print '<TD>';

                    print "<A HREF='javascript:;' OnClick='window.open(\"setpermission.php?file="
                          . $fileName
                          . '&perms='
                          . $myDir['perms']
                          . "\",\"permissions\",\"width=250,height=150,scrollbars=no,menubar=no,status=yes,directories=no,location=no\")'><IMG SRC='../img/settings.gif' WIDTH='20' HEIGHT='20' BORDER=0 ALT='Change permissions'></A>";

                    print "</TD>\n";

                    if ('zip' == mb_strtolower($myDir['extension'])) {
                        print "<TD><A HREF='javascript:ConfirmationUnzip(\"" . $PHP_SELF . '?action=unzipfile&file=' . $myDir['name'] . '&currentDir=' . $currentDir . "\")'><IMG SRC=../img/zip.gif BORDER=0 ALT=\"Unzip\"></A></TD>\n";
                    } else {
                        echo '<TD>&nbsp;</td>';
                    }

                    print "</TR>\n";
                }
            }
        }

        print '	</TABLE>';
    } else {
        if (!isset($msg)) {
            $msg = "Could not connect to server $server:$port with user $user<P><A HREF='" . $PHP_SELF . "?logoff=true'>Try again...</A>";
        }

        include 'admin_footer.php'; ?>
        <HTML>
        <HEAD>
            <LINK REL=StyleSheet HREF="style/cm.css" TITLE=Contemporary TYPE="text/css">
            <SCRIPT LANGUAGE="JavaScript" SRC="include/script.js"></SCRIPT>
        </HEAD>
        <BODY>
        <?php
        print $msg;
    }
} else { // Still need to logon...
    ?>
    <HTML>
    <HEAD>
        <LINK REL=StyleSheet HREF="style/cm.css" TITLE=Contemporary TYPE="text/css">
        <SCRIPT LANGUAGE="JavaScript" SRC="include/script.js"></SCRIPT>
    </HEAD>
    <BODY>
    <TABLE BORDER=0 CELLPADDING=2 CELLSPACING=0 WIDTH='100%'>
        <TR>
            <TD CLASS=menu>
                <B>WebFTP Version 1.4</B>
            </TD>
        </TR>
    </TABLE>

    <FORM NAME=logon action='<?= $PHP_SELF; ?>' METHOD=POST>
        <TABLE>
            <TR>
                <TD>Server</TD>
                <TD><INPUT TYPE=TEXT NAME=server SIZE=18>&nbsp;Port : <INPUT TYPE=TEXT NAME=port SIZE=6 VALUE=21></TD>
            </TR>
            <TR>
                <TD>User</TD>
                <TD>
                    <INPUT TYPE=TEXT NAME=user SIZE=18>
                    <INPUT TYPE="checkbox" NAME="anonymous" VALUE=1 OnClick="anonymousAccess()"> : Anonymous access
                </TD>
            </TR>
            <TR>
                <TD>Password</TD>
                <TD><INPUT TYPE=PASSWORD NAME=password SIZE=18></TD>
            </TR>
            <TR>
                <TD COLSPAN=2 ALIGN=CENTER><INPUT TYPE=SUBMIT VALUE="Log on"></TD>
            </TR>
        </TABLE>
    </FORM>

    <?php
}
?>
    <P>
        <DIV ALIGN=CENTER STYLE='font-family: verdana, arial, sans-serif; font-size:10px'>


    <P>
        Xoopsé by &copy; 2002, <A HREF="http://www.inconnueteam.net">InconnueTeam</A>
        </DIV>
</BODY>
    </HTML>


<?php
