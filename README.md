# Unicenta POS Reports using PHP

I have setup and maintain the POS of a client. The reports do not show discounts on transactions.
I did not feel comfortable modifying the source code. Since the database used is MySQL, I created this report tool in PHP.

SQL to show tickets
```
SELECT 
  DISTINCT t.ticketid,
  datenew AS `date`, 
  pp.id as personid, 
  pp.name AS person,
  payment AS type, 
  total, 
  tendered,
  receipt
FROM
  receipts r INNER JOIN payments p ON r.id = p.receipt
  INNER JOIN tickets t ON t.id = r.id
  INNER JOIN ticketlines tl ON tl.ticket = t.id
  INNER JOIN people pp ON pp.id = t.person
ORDER BY 
  r.datenew DESC LIMIT 10;
```

SQL to show tickets that include discount
```
SELECT 
  distinct(ticket) 
FROM 
  ticketlines 
WHERE 
  attributes LIKE '%Discount%';
```

SQL to show tickets within a date range
```
SELECT 
  DISTINCT ticketid,
  datenew AS `date`, 
  pp.id as personid, 
  pp.name AS person,
  payment AS type, 
  total,   
  receipt,
  tendered
FROM
  receipts r
    INNER JOIN payments p ON r.id = p.receipt
  INNER JOIN tickets t ON t.id = r.id
  INNER JOIN ticketlines tl ON tl.ticket = t.id
  INNER JOIN people pp ON pp.id = t.person
WHERE
  (r.datenew BETWEEN '2023-01-01 00:00:00' AND '2023-01-01 23:00:00') 
ORDER BY 
  r.datenew ASC;
```
SQL to show tickets for an employee
```
SELECT 
  DISTINCT t.ticketid,
  datenew AS `date`, 
  pp.id as personid, 
  pp.name AS person, 
  payment AS type, 
  total, 
  receipt,
  tendered
FROM
  receipts r
    INNER JOIN payments p ON r.id = p.receipt
  INNER JOIN tickets t ON t.id = r.id
  INNER JOIN ticketlines tl ON tl.ticket = t.id
  INNER JOIN people pp ON pp.id = t.person
WHERE
  pp.id = '**personid**'				
ORDER BY 
  r.datenew DESC;
```

# Ticket with id 1890 printed

![ticket 1890](https://github.com/wilwad/php-reporting-for-unicentaopos/blob/main/ticket-1890.png?raw=true)

# How ticket 1890 looks in our PHP web report

![report for ticket 1890](https://github.com/wilwad/php-reporting-for-unicentaopos/blob/main/1890-report.png?raw=true)

# Our Reporting tool with the basic functions

![PHP reporting for UnicentaPOS](https://github.com/wilwad/php-reporting-for-unicentaopos/blob/main/php-reporting.png?raw=true)

# Now we can create a preview of the receipt and save as a PDF to email to a client ~ 

![PHP reporting for UnicentaPOS](https://github.com/wilwad/php-reporting-for-unicentaopos/blob/main/send-receipt-by-email.png?raw=true)
