ie=false;
nn=false;
if(document.all) ie = true;
if(document.layers) nn = true;

function anonymousAccess()
{
	if(document.logon.user.value == 'anonymous')
	{
	  document.logon.user.value = '';
	  document.logon.user.focus();
	} else {
	  document.logon.user.value = 'anonymous';
	  document.logon.password.focus();
	}
};

function submitForm(action, file, file2)
{
  document.actionform.action.value = action;
  document.actionform.file.value = file;
  document.actionform.file2.value = file2;
  document.actionform.submit();
};

function createDirectory(directory)
{
  if(directory)
  {submitForm("createdir", directory);}
  else
  {alert('Enter a directory name first');}
};

function changeMode(mode)
{
  document.actionform.mode.value = mode;
  document.putForm.mode.value = mode;
  mode==1?document.currentMode.showmode.value = "FTP_BINARY":document.currentMode.showmode.value = "FTP_ASCII";
};

function renameFile(oldName)
{
  newName = window.prompt("Enter the new name for "+oldName,""+oldName);
  if(newName)
    {submitForm("rename", oldName, newName);}
    else
    {alert('Please enter a new name');}
};

function Confirmation(URL)
{
  if (confirm("Really delete this Item ?\n"))
  {location = String(URL);}
  else
  {
	  //Do nothing
  }
};

function ConfirmationUnzip(URL)
{
  if (confirm("Unzip File in the current dir ?\n"))
  {location = String(URL);}
};