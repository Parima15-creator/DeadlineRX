const { GoogleGenerativeAI } = require("@google/generative-ai");

const buildPrompt = require("../services/promptBuilder");

const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);

exports.generatePlan = async (req, res) => {

    try {

        const tasks = req.body.tasks || [];

        const availableHoursToday =
            req.body.available_hours_today || 3;

        const extraContext =
            req.body.extra_ai_context || "";

        const studentName =
            req.body.student_name || "Student";

        const pendingTasks = tasks.filter(task => !task.is_completed);

        if (pendingTasks.length === 0) {

            return res.json({
                success: true,
                plan: "All tasks are completed."
            });

        }

        const prompt = buildPrompt(
            studentName,
            availableHoursToday,
            extraContext,
            pendingTasks
        );

        const model = genAI.getGenerativeModel({
            model: "gemini-3.5-flash"
        });

        const result = await model.generateContent(prompt);

        const plan = result.response.text();

        res.json({
            success: true,
            plan
        });

    }

    catch (err) {

        console.log(err);

        res.status(500).json({
            success: false,
            error: err.message
        });

    }

};