# kiken
Money Manager

## Setup

1. use ressource/db_template.sql to create needed tables
2. add a user in user table (name, password)
3. add an account in account table : 
  - account name : eg "BNP compte courrant"
  - solde : this is the bank solde account at the kiken db creation time, futures transactions will increment or decrement this value.
  - user_id : the id of user from kiken.user table created in 2)
4. set your db info in connect.inc.php

## Access

entry point is main.php

## Usage

* Add transactions from the "Add Transaction" link
  * Date are in US format (YYYY-MM-DD)
  * expenses are negatives values with "-" sign, eg -100.21
  * incomes are positives values (no sign need), eg 50.10
  * Use tags ! A transaction can have multiples tags (separated by comma)

## Tags

Tags are the reason of kiken, there are much powerfull that categories.

Tags will be the key to get statistics, eg :
You have a house and a car insurance, you may want to tag house transaction with "insurance, house" and the car with "insurance, car".
This way, you will later be able to get a statistic on insurance global expenses (how much do I spend for insurance), or car global expenses (how much do I spend for my car), {insurance,car} global expenses (how muchdo I spend for my car's insurance).

You may also want to add a "recurrent" tag :
If you tag with "recurrent" the expenses that occur every month (ie insurance, taxes, gaz monthly bill, etc...), it will be easy to get a statistic on the evolution of the monthly recurrents expenses.

