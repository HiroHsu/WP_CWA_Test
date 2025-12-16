// CWA API Types based on W-C0033-001 structure

export interface CWAApiResponse {
  success: string;
  result: {
    resource_id: string;
    fields: Array<{ id: string; type: string }>;
  };
  records: {
    record: Array<CWARecord>;
  };
}

export interface CWARecord {
  datasetDescription: string;
  location: Array<CWALocation>;
}

export interface CWALocation {
  locationName: string;
  hazardConditions: {
    hazards: Array<CWAHazard>;
  };
}

export interface CWAHazard {
  info: {
    language: string;
    phenomena: string;
    significance: string;
    affectedAreas: {
      location: Array<{
        locationName: string;
      }>;
    };
  };
  validTime: {
    startTime: string;
    endTime: string;
  };
}

// Internal App Types

export interface WeatherAlert {
  id: string;
  location: string;
  phenomena: string; // e.g., "豪雨"
  significance: string; // e.g., "特報"
  startTime: string;
  endTime: string;
  fullText: string;
}

export interface AIAnalysis {
  summary: string;
  safetyTips: string[];
  severityLevel: 'low' | 'medium' | 'high';
}
