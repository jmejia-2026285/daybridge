' Ejecuta el planificador de Daybridge sin mostrar ninguna ventana.
' La tarea de Windows "Daybridge Planificador" invoca este archivo cada minuto.
Set shell = CreateObject("WScript.Shell")
shell.Run "cmd /c ""C:\laragon\www\daybridge-completo\ejecutar-planificador.bat""", 0, False
