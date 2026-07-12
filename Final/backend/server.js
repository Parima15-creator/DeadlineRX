const express = require("express");
const cors = require("cors");
const path = require("path");
const dotenv = require("dotenv");

dotenv.config({
    path: path.join(__dirname, ".env")
});

const db = require("./config/db");

// Import routes FIRST
const taskRoutes = require("./routes/tasks");
const aiRoutes = require("./routes/ai");

const app = express();

app.use(cors());
app.use(express.json());

// THEN use them
app.use("/api", taskRoutes);
app.use("/api", aiRoutes);

app.get("/", (req, res) => {
    res.send("DeadlineRX Backend Running 🚀");
});

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});