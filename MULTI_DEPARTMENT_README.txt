MULTI-DEPARTMENT LEAVE MANAGEMENT SYSTEM

Demo password for every account: 123456

EEE: ragavarshini / 24BEE01, sowmiya / 24BEE02 -> advisor_eee -> hod_eee
CSBS: sujatha / 24BCB01, libika / 24BCB02 -> advisor_csbs -> hod_csbs
IT: theshitha / 24BIT01, tharanya / 24BIT02 -> advisor_it -> hod_it

Branch, department, batch, semester and academic year are stored in the student profile.
Department routing is enforced by the assigned advisor_id and hod_id. An IT request goes only to IT Advisor and IT HOD; CSBS and EEE are isolated similarly.

FIRST RUN:
1. Put the newtemplate folder under C:\xampp\htdocs\new_proj\
2. Start Apache and MySQL.
3. Open http://localhost/new_proj/newtemplate/create_users.php once.
4. Then open http://localhost/new_proj/newtemplate/login.php

The application also auto-seeds these demo users through db.php, so login does not depend on importing SQL manually.
