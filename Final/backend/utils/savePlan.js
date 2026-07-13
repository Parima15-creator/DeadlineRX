const db = require("../config/db");

function savePlan(studentEmail, planText) {

    return new Promise((resolve, reject) => {

        const sql = `
            INSERT INTO ai_plans
            (student_email, plan_text)
            VALUES (?, ?)
        `;

        db.query(sql, [studentEmail, planText], (err, result) => {

            if (err) {
                reject(err);
            } else {
                resolve(result);
            }

        });

    });

}

module.exports = savePlan;