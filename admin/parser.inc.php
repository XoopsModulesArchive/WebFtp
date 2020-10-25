<?php

function parse_ftp_rawlist($list, $type = 'UNIX')
{
    if ('UNIX' == $type) {
        $regexp = '([-ldrwxs]{10})[ ]+([0-9]+)[ ]+([A-Z|0-9|-]+)[ ]+([A-Z|0-9|-]+)[ ]+([0-9]+)[ ]+([A-Z]{3}[ ]+[0-9]{1,2}[ ]+[0-9|:]{4,5})[ ]+(.*)';

        $i = 0;

        foreach ($list as $line) {
            $is_dir = $is_link = false;

            $target = '';

            if (eregi($regexp, $line, $regs)) {
                if (!eregi('^[.]', $regs[7])) { //hide hidden files
                    if (!eregi('^[.]{2}', $regs[7])) { // don't hide hidden files
                        $i++;

                        if (eregi('^[d]', $regs[1])) {
                            $is_dir = true;
                        } elseif (eregi('^[l]', $regs[1])) {
                            $is_link = true;

                            [$regs[7], $target] = preg_split(' -> ', $regs[7]);
                        }

                        //Get extension from file name

                        $regs_ex = explode('.', $regs[7]);

                        if ((!$is_dir) && (count($regs_ex) > 1)) {
                            $extension = $regs_ex[count($regs_ex) - 1];
                        } else {
                            $extension = '';
                        }

                        $files[$i] = [
                            'is_dir' => $is_dir,
'extension' => $extension,
'name' => $regs[7],
'perms' => $regs[1],
'num' => $regs[2],
'user' => $regs[3],
'group' => $regs[4],
'size' => $regs[5],
'date' => $regs[6],
'is_link' => $is_link,
'target' => $target,
                        ];
                    }
                }
            }
        }
    } else {
        $regexp = "([0-9\-]{8})[ ]+([0-9:]{5}[APM]{2})[ ]+([0-9|<DIR>]+)[ ]+(.*)";

        foreach ($list as $line) {
            $is_dir = false;

            if (eregi($regexp, $line, $regs)) {
                if (!eregi('^[.]', $regs[4])) {
                    if ('<DIR>' == $regs[3]) {
                        $is_dir = true;

                        $regs[3] = '';
                    }

                    $i++;

                    // Get extension from filename

                    $regs_ex = explode('.', $regs[4]);

                    if ((!$is_dir) && (count($regs_ex) > 1)) {
                        $extension = $regs_ex[count($regs_ex) - 1];
                    } else {
                        $extension = '';
                    }

                    $files[$i] = [
                        'is_dir' => $is_dir,
'extension' => $extension,
'name' => $regs[4],
'date' => $regs[1],
'time' => $regs[2],
'size' => $regs[3],
'is_link' => 0,
'target' => '',
'num' => '',
                    ];
                }
            }
        }
    }

    if (is_array($files) and count($files) > 0) {
        asort($files);

        reset($files);
    }

    return $files;
}
