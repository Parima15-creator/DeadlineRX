const db = require("../config/db");

exports.getLatestPlan = (req, res) => {

    const studentEmail = req.query.student_email;

    if (!studentEmail) {

        return res.status(400).json({
            success: false,
            message: "Student email required."
        });

    }

    const sql = `
        SELECT plan_text
        FROM ai_plans
        WHERE student_email = ?
        ORDER BY created_at DESC
        LIMIT 1
    `;

    db.query(sql, [studentEmail], (err, rows) => {

        if (err) {

            console.log(err);

            return res.status(500).json({
                success: false,
                message: "Database error."
            });

        }

        if (rows.length === 0) {

            return res.json({
                success: true,
                has_plan: false
            });

        }

        res.json({

            success: true,

            has_plan: true,

            plan: rows[0].plan_text

        });

    });

};