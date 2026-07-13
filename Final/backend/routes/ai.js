const express = require("express");

const router = express.Router();

const aiController = require("../controllers/aiController");

const latestPlanController =
require("../controllers/latestPlanController");

router.post(
    "/generate-plan",
    aiController.generatePlan
);

router.get(
    "/latest-plan",
    latestPlanController.getLatestPlan
);

module.exports = router;