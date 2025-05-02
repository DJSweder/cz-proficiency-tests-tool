This tool simply searches for questions from professional competency tests for the Czech Republic in the sections of: Insurance, Loans, Capital Market (Investments), Distribution of Pension Products. Correct answers are graphically distinguished. There is also a toolbar that serves as a simulation of incorrect answers with an evaluation of success.

# Instalation

Simply copy content of */src* folder to webserver with PHP support

# Data source

The database is loaded by importing XML files from the Czech National Bank via the *import_xml.php* script, which expects files in the import directory. Questions about pension products are imported via *import_penze.php*, which expects html from the pages of the Association of Banks of the Czech Republic stored in the import directory.

# Usage

Use search field and enjoy!
