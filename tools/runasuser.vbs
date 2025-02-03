'Starts a command in a non-elevated privileges state. If your script runs as admin, you might want to
'start a command as the non-admin user that started your script. This script can do that for you.
'
'The way how it achieves that, is, it creates a scheduled task with a unique name,
'and it runs that task with unelevated privileges.
'
'Example: runasuser xampp-restart.vbs
'
''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''''

' Define the task name
taskName = "runasuser-8ac5510b-ebb4-46e1-888b-94bb76fb41b1"

' Define the command to run:
cmdCommand = ""
cmdParams = ""
if WScript.Arguments.Count >= 1 then
	cmdCommand = WScript.Arguments(0)
end if
if WScript.Arguments.Count >= 2 then
	cmdParams = WScript.Arguments(1)
end if

if instr(cmdCommand, ":") = 0 then
	'Relative Pfadangabe:
	'->Current Directory mit angeben, der Aufgabenplaner braucht den Pfad absolut...
	Set shell = CreateObject("WScript.Shell")
	currentDirectory = shell.CurrentDirectory
	cmdCommand = currentDirectory & "\" & cmdCommand
end if

' Create a new scheduled task that runs cmd.exe with the specified parameters, without elevated privileges
Set WshShell = CreateObject("WScript.Shell")

' Command to create the task with cmd parameters
createTaskCmd = "SCHTASKS /Create /TN """ & taskName & """ /TR """ & cmdCommand & " " & cmdParams & """ /SC ONCE /ST 00:00 /F /RL LIMITED"

' Create the task
WshShell.Run createTaskCmd, 0, True 'This command runs hidden (0 = hidden, True = wait until it finishes)

' Run the task immediately
runTaskCmd = "SCHTASKS /Run /TN """ & taskName & """"
WshShell.Run runTaskCmd, 0, False 'This runs the task and hides the window

' Optional: Delete the task after running it
deleteTaskCmd = "SCHTASKS /Delete /TN """ & taskName & """ /F"
WshShell.Run deleteTaskCmd, 0, True
