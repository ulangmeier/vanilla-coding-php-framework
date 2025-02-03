Set objShell = CreateObject("WScript.Shell")

' Beende den Xampp-Dienst
objShell.Run "C:\xampp\xampp_stop.exe", 0, True '0 = Versteckt, True = Warte auf Beendigung

' Starte den Xampp-Dienst
objShell.Run "C:\xampp\xampp_start.exe", 0, False '0 = Versteckt, False = Nicht warten, der Task muss weiterlaufen
