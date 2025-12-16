import { GoogleGenAI, Type } from "@google/genai";
import { WeatherAlert, AIAnalysis } from '../types';

// Initialize Gemini
// Note: In a production app, the API key should be handled more securely or via backend proxy.
// For this demo, we rely on the env var or user input if implemented.
const getAIClient = () => {
  const apiKey = process.env.API_KEY;
  if (!apiKey) {
    throw new Error("Gemini API Key is missing.");
  }
  return new GoogleGenAI({ apiKey });
};

export const analyzeWarnings = async (alerts: WeatherAlert[]): Promise<AIAnalysis> => {
  if (alerts.length === 0) {
    return {
      summary: "目前台灣地區沒有發布主要氣象警報。",
      safetyTips: ["出門仍請注意天氣變化。", "快樂出門，平安回家。"],
      severityLevel: 'low'
    };
  }

  const ai = getAIClient();
  
  // Consolidate alerts for the prompt
  const alertSummary = alerts.map(a => 
    `- ${a.location}: ${a.phenomena} (${a.significance}) Valid: ${a.startTime} to ${a.endTime}`
  ).join('\n');

  const prompt = `
    You are a professional meteorologist and safety advisor.
    Analyze the following Taiwan weather warnings:
    ${alertSummary}

    Provide a response in JSON format with the following structure:
    1. summary: A concise paragraph summarizing the overall weather threat in Taiwan (in Traditional Chinese).
    2. safetyTips: A list of 3-5 specific, actionable safety tips for citizens in the affected areas (in Traditional Chinese).
    3. severityLevel: One of 'low', 'medium', 'high' based on the overall threat.

    Focus on public safety and clear communication.
  `;

  try {
    const response = await ai.models.generateContent({
      model: 'gemini-2.5-flash',
      contents: prompt,
      config: {
        responseMimeType: "application/json",
        responseSchema: {
          type: Type.OBJECT,
          properties: {
            summary: { type: Type.STRING },
            safetyTips: { 
              type: Type.ARRAY,
              items: { type: Type.STRING }
            },
            severityLevel: { type: Type.STRING, enum: ['low', 'medium', 'high'] }
          }
        }
      }
    });

    const text = response.text;
    if (!text) {
      throw new Error("Empty response from AI");
    }

    return JSON.parse(text) as AIAnalysis;

  } catch (error) {
    console.error("Gemini Analysis Failed:", error);
    return {
      summary: "無法連線至 AI 分析服務，請參考官方警報內容。",
      safetyTips: ["請隨時關注中央氣象署最新消息。"],
      severityLevel: 'medium'
    };
  }
};
