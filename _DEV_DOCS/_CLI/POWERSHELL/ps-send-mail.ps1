# Define the sender, recipient, subject, and body of the email
$From = "queen@unimatrix.local"
$To = "vsevolod.azovsky@gmail.com"
$Subject = "Test Subject"
$Body = "Test Body"
 
# Define the SMTP server details
$SMTPServer = "192.168.1.120"
$SMTPPort = 25
$SMTPUsername = "queen@unimatrix.local"
$SMTPPassword = "9601378862"
 
# Create a new email object
$Email = New-Object System.Net.Mail.MailMessage
$Email.From = $From
$Email.To.Add($To)
$Email.Subject = $Subject
$Email.Body = $Body
# Uncomment below to send HTML formatted email
#$Email.IsBodyHTML = $true
 
# Create an SMTP client object and send the email
$SMTPClient = New-Object System.Net.Mail.SmtpClient($SMTPServer, $SMTPPort)
$SMTPClient.EnableSsl = $false
 
$SMTPClient.Credentials = New-Object System.Net.NetworkCredential($SMTPUsername, $SMTPPassword)
$SMTPClient.Send($Email)
 
# Output a message indicating that the email was sent successfully
Write-Host "Email sent successfully to $($Email.To.ToString())"
Read-Host -Prompt "Press Enter to exit"