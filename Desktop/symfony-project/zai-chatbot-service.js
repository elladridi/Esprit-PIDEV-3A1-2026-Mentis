const express = require('express');
const cors = require('cors');

const app = express();
const PORT = 3000;

app.use(cors());
app.use(express.json());

let zai;

(async () => {
    try {
        const ZAI = require('z-ai-web-dev-sdk');
        
        // Try different initialization methods
        if (typeof ZAI.create === 'function') {
            zai = await ZAI.create();
        } else if (typeof ZAI.default?.create === 'function') {
            zai = await ZAI.default.create();
        } else if (typeof ZAI === 'function') {
            zai = await ZAI();
        } else if (typeof ZAI.default === 'function') {
            zai = await ZAI.default();
        } else {
            zai = ZAI;
        }
        
        console.log('✅ Z.ai SDK initialized');
    } catch (error) {
        console.error('⚠️ Z.ai SDK initialization error:', error.message);
        // Continue without SDK for now - will use mock responses
    }
})();

app.post('/api/chat', async (req, res) => {
    try {
        const { message } = req.body;

        if (!message || message.trim() === '') {
            return res.status(400).json({ error: 'Message is required' });
        }

        if (zai && zai.chat?.completions?.create) {
            const completion = await zai.chat.completions.create({
                messages: [
                    { 
                        role: 'system', 
                        content: 'You are a helpful mental health and wellness assistant. Provide supportive, evidence-based guidance for mental health questions.' 
                    },
                    { role: 'user', content: message }
                ],
            });
            const reply = completion.choices[0].message.content;
            res.json({ reply });
        } else {
            // Fallback response for testing
            res.json({ 
                reply: '(Mock Response) I understand your concern about: ' + message.substring(0, 50) + '. As a mental health assistant, I\'m here to support you. Please reach out to a professional if you need immediate help.' 
            });
        }
    } catch (error) {
        console.error('Chat error:', error);
        res.status(500).json({ error: 'Failed to generate response', details: error.message });
    }
});

app.post('/api/summarize', async (req, res) => {
    try {
        const { title, description } = req.body;

        if (!description || description.trim() === '') {
            return res.status(400).json({ error: 'Description is required' });
        }

        if (zai && zai.chat?.completions?.create) {
            const prompt = `Please provide a concise 2-3 sentence summary of the following content:

Title: ${title}
Description: ${description}

Summary:`;

            const completion = await zai.chat.completions.create({
                messages: [
                    { 
                        role: 'system', 
                        content: 'You are a content summarization expert. Provide clear, concise summaries.' 
                    },
                    { role: 'user', content: prompt }
                ],
            });

            const summary = completion.choices[0].message.content.trim();
            res.json({ summary });
        } else {
            // Fallback summary for testing
            const wordCount = description.split(' ').length;
            const mockSummary = `This content about "${title}" focuses on key wellness concepts. ${description.substring(0, 100)}... The material provides valuable insights for personal development and health.`;
            res.json({ summary: mockSummary });
        }
    } catch (error) {
        console.error('Summarize error:', error);
        res.status(500).json({ error: 'Failed to generate summary', details: error.message });
    }
});

app.post('/api/generate-image', async (req, res) => {
    try {
        const { prompt } = req.body;

        if (!prompt || prompt.trim() === '') {
            return res.status(400).json({ error: 'Prompt is required' });
        }

        const response = await zai.images.generations.create({
            prompt: prompt,
            size: '1024x1024'
        });

        const imageBase64 = response.data[0].base64;
        res.json({ image: `data:image/png;base64,${imageBase64}` });
    } catch (error) {
        console.error('Image generation error:', error);
        res.status(500).json({ error: 'Failed to generate image' });
    }
});

app.listen(PORT, () => {
    console.log(`🚀 Z.ai Chatbot Service running on http://localhost:${PORT}`);
});
