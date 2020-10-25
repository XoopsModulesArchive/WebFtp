<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
    <TITLE> New Document </TITLE>
    <LINK REL=StyleSheet HREF="style/cm.css" TITLE=Contemporary TYPE="text/css">

    <SCRIPT LANGUAGE="JavaScript">
        <!--
        permission = "<?=$perms; ?>";
        Or = permission.substring(1, 2);
        Gr = permission.substring(4, 5);
        Pr = permission.substring(7, 8);

        Ow = permission.substring(2, 3);
        Gw = permission.substring(5, 6);
        Pw = permission.substring(8, 9);

        Ox = permission.substring(3, 4);
        Gx = permission.substring(6, 7);
        Px = permission.substring(9, 10);

        function setPermissions() {
            focus();
            if (Or != "-") {
                permissions.iOr.checked = true
            }
            if (Gr != "-") {
                permissions.iGr.checked = true
            }
            if (Pr != "-") {
                permissions.iPr.checked = true
            }

            if (Ow != "-") {
                permissions.iOw.checked = true
            }
            if (Gw != "-") {
                permissions.iGw.checked = true
            }
            if (Pw != "-") {
                permissions.iPw.checked = true
            }

            if (Ox != "-") {
                permissions.iOx.checked = true
            }
            if (Gx != "-") {
                permissions.iGx.checked = true
            }
            if (Px != "-") {
                permissions.iPx.checked = true
            }
        }

        function changePermissions() {
            O = 0;
            P = 0;
            G = 0;

            if (permissions.iOr.checked === true) {
                O = O + 4
            }
            if (permissions.iGr.checked === true) {
                G = G + 4
            }
            if (permissions.iPr.checked === true) {
                P = P + 4
            }

            if (permissions.iOw.checked === true) {
                O = O + 2
            }
            if (permissions.iGw.checked === true) {
                G = G + 2
            }
            if (permissions.iPw.checked === true) {
                P = P + 2
            }

            if (permissions.iOx.checked === true) {
                O = O + 1
            }
            if (permissions.iGx.checked === true) {
                G = G + 1
            }
            if (permissions.iPx.checked === true) {
                P = P + 1
            }

            window.opener.actionform.file.value = "<?=$file; ?>";
            window.opener.actionform.permissions.value = O + "" + G + "" + P;
            window.opener.actionform.action.value = "chmod";
            window.opener.actionform.submit()
        }

        //-->
    </SCRIPT>
</HEAD>

<BODY OnLoad="setPermissions()">
<FORM NAME=permissions>
    <TABLE>
        <TR>
            <TD ALIGN=CENTER><B>Owner</B></TD>
            <TD ALIGN=CENTER><B>Group</B></TD>
            <TD ALIGN=CENTER><B>Public</B></TD>
        </TR>
        <TR>
            <TD><INPUT TYPE="checkbox" NAME="iOr"> Read</TD>
            <TD><INPUT TYPE="checkbox" NAME="iGr"> Read</TD>
            <TD><INPUT TYPE="checkbox" NAME="iPr"> Read</TD>
        </TR>
        <TR>
            <TD><INPUT TYPE="checkbox" NAME="iOw"> Write</TD>
            <TD><INPUT TYPE="checkbox" NAME="iGw"> Write</TD>
            <TD><INPUT TYPE="checkbox" NAME="iPw"> Write</TD>
        </TR>
        <TR>
            <TD><INPUT TYPE="checkbox" NAME="iOx"> Execute</TD>
            <TD><INPUT TYPE="checkbox" NAME="iGx"> Execute</TD>
            <TD><INPUT TYPE="checkbox" NAME="iPx"> Execute</TD>
        </TR>
    </TABLE>
    <INPUT TYPE=button OnClick='changePermissions()' VALUE='Set Permissions'>
</FORM>
</BODY>
</HTML>
