const express = require("express");
const router = express.Router();

const db = require("../config/db");

router.get("/students", (req, res) => {

    db.query("SELECT * FROM student", (err, result) => {

        if (err)
            return res.status(500).json(err);

        res.json(result);

    });

});

module.exports = router;