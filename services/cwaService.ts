import { CWAApiResponse, WeatherAlert } from '../types';

const CWA_API_URL = 'https://opendata.cwa.gov.tw/api/v1/rest/datastore/W-C0033-001';

export const fetchWeatherWarnings = async (apiKey: string): Promise<WeatherAlert[]> => {
  if (!apiKey) {
    throw new Error('請輸入 CWA API Key');
  }

  const url = `${CWA_API_URL}?Authorization=${apiKey}&format=JSON`;
  
  try {
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`CWA API Error: ${response.status} ${response.statusText}`);
    }

    const data: CWAApiResponse = await response.json();
    
    if (data.success !== 'true') {
      throw new Error('API request failed according to response body.');
    }

    const alerts: WeatherAlert[] = [];
    
    // Parse the nested structure
    // structure: records -> record[] -> location[] -> hazardConditions -> hazards[]
    const records = data.records.record;
    
    records.forEach(record => {
      record.location.forEach(loc => {
        const hazards = loc.hazardConditions.hazards;
        if (hazards && hazards.length > 0) {
          hazards.forEach((hazard, index) => {
             // Create a unique ID for React keys
             const id = `${loc.locationName}-${hazard.info.phenomena}-${index}-${Date.now()}`;
             
             alerts.push({
               id,
               location: loc.locationName,
               phenomena: hazard.info.phenomena,
               significance: hazard.info.significance,
               startTime: hazard.validTime.startTime,
               endTime: hazard.validTime.endTime,
               fullText: `${loc.locationName} ${hazard.info.phenomena}${hazard.info.significance}`
             });
          });
        }
      });
    });

    return alerts;

  } catch (error) {
    console.error("Failed to fetch weather data:", error);
    throw error;
  }
};
