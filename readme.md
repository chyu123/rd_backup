# RD Backup
####Objective
[Radiusdesk ](https://radiusdesk.com/ "Radiusdesk ")is the software to manage wifi account with feature of radius that can integrated with network firewall feature that called **"captive Portal"**.
But there is a mystery problem that when we use it for longtime, without handly touch anything, data in table **rdcheck** will get lost.
This script create with PHP to solve this problem by backup table radcheck + radreply to another database then recovery when we need.

**Caution**
I WILL NOT HAVE ANY RESPONSIBLE FOR ANY DAMAGE TO YOUR SYSTEM WHEN USING WITH THESE SCRIPTS.

------------


####Usage
**list of script files:**
1. config.ini - config file
	- contain all config about database and password salt.

2. backup.php - backup script
	`php backup.php`

3. recovery.php - recovery script
	`php recovery.php [routine_id] [recover_permanent_user]`

	- `[routine_id]` is the batch number of backup set, format:number **(option)**
	- `[recover_permanent_user]` is boolean, if '1' or 'y', it will recover table permanent_users also. **(option)**

	**Example:**
		`php recovery.php 2 y` 
		-  recovery with routine_id=2 with recovery table 'permanent_users'
		
		`php recovery.php ` 
		-  recovery with last routine_id with **no** recovery table 'permanent_users'

4. log_viewer.php - log reader script
	`php log_viewer.php [OPTION]`
	
	- `--ev=WORD` 
	search event detail, format:text
	- `--pr=WORD` search process detail, format:text
	- `--st=DATETIME` start time that log get record, format:'YYYY-mm-dd HH-MM-SS'
	- `--et=DATETIME` end time that log get record, format:'YYYY-mm-dd HH-MM-SS'
	
		**Example:**
		`php log_viewer.php --ev=text1 --pr=text2 --st=2021-01-01 --et=2021-02-02` 
		- List log that have 
			- event_log record contain text "text1"
			- process_log record contain text "text2"
			- record after "2021-01-01 00:00:00" but before "2021-02-02 23:59:59"

------------
####Credit
1. [Dirk van der Walt](https://sourceforge.net/u/dvdwalt/profile/ "Dirk van der Walt") - who created **Radiusdesk** with [GNU General Public License version 2.0 (GPLv2)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html).
2. [Rich Jenkins](https://richjenks.com/) - for the [idea of PHP CLI Table](https://richjenks.com/php-cli-table/).