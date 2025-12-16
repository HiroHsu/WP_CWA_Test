import React, { useState, useEffect } from 'react';
import { AlertCircle, RefreshCw, Settings, Info } from 'lucide-react';
import { fetchWeatherWarnings } from './services/cwaService';
import { analyzeWarnings } from './services/geminiService';
import { AlertCard } from './components/AlertCard';
import { SettingsModal } from './components/SettingsModal';
import { AIAnalyst } from './components/AIAnalyst';
import { WeatherAlert, AIAnalysis } from './types';

// Initial dummy data or empty state
const App: React.FC = () => {
  const [cwaKey, setCwaKey] = useState<string>(() => localStorage.getItem('CWA_API_KEY') || '');
  const [alerts, setAlerts] = useState<WeatherAlert[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);
  const [isSettingsOpen, setIsSettingsOpen] = useState(false);
  
  // AI State
  const [aiAnalysis, setAiAnalysis] = useState<AIAnalysis | null>(null);
  const [aiLoading, setAiLoading] = useState(false);

  const loadData = async (key: string) => {
    if (!key) return;
    setLoading(true);
    setError(null);
    try {
      const data = await fetchWeatherWarnings(key);
      setAlerts(data);
      setLastUpdated(new Date());
      // Reset AI analysis when new data comes in
      setAiAnalysis(null);
    } catch (err: any) {
      setError(err.message || '無法取得資料');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (cwaKey) {
      loadData(cwaKey);
    }
  }, [cwaKey]);

  const handleSaveKey = (key: string) => {
    localStorage.setItem('CWA_API_KEY', key);
    setCwaKey(key);
  };

  const handleRunAnalysis = async () => {
    setAiLoading(true);
    try {
      const analysis = await analyzeWarnings(alerts);
      setAiAnalysis(analysis);
    } catch (e) {
      console.error(e);
    } finally {
      setAiLoading(false);
    }
  };

  return (
    <div className="min-h-screen pb-12">
      {/* Header */}
      <header className="bg-white shadow-sm sticky top-0 z-30">
        <div className="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="bg-blue-600 p-2 rounded-lg">
              <AlertCircle className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-gray-900 leading-tight">
                台灣天氣警示儀表板
              </h1>
              <p className="text-xs text-gray-500 hidden sm:block">
                資料來源：中央氣象署 (CWA) Open Data API
              </p>
            </div>
          </div>
          
          <div className="flex items-center gap-2">
             {lastUpdated && (
              <span className="text-xs text-gray-400 hidden sm:inline-block mr-2">
                更新於: {lastUpdated.toLocaleTimeString()}
              </span>
            )}
            <button 
              onClick={() => loadData(cwaKey)}
              disabled={loading || !cwaKey}
              className="p-2 hover:bg-gray-100 rounded-lg text-gray-600 transition-colors disabled:opacity-50"
              title="重新整理"
            >
              <RefreshCw className={`w-5 h-5 ${loading ? 'animate-spin' : ''}`} />
            </button>
            <button 
              onClick={() => setIsSettingsOpen(true)}
              className="p-2 hover:bg-gray-100 rounded-lg text-gray-600 transition-colors"
              title="設定 API Key"
            >
              <Settings className="w-5 h-5" />
            </button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-5xl mx-auto px-4 py-8">
        
        {/* API Key Prompt */}
        {!cwaKey && (
          <div className="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-lg mb-8 shadow-sm">
            <div className="flex items-start gap-4">
              <Info className="w-6 h-6 text-yellow-600 flex-shrink-0 mt-1" />
              <div>
                <h3 className="font-bold text-yellow-800 text-lg mb-1">尚未設定 API Key</h3>
                <p className="text-yellow-700 mb-4">
                  請點擊右上方設定按鈕，輸入您的 CWA Open Data API 授權碼以開始使用。
                </p>
                <button 
                  onClick={() => setIsSettingsOpen(true)}
                  className="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors font-medium text-sm"
                >
                  前往設定
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Error State */}
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
             <AlertCircle className="w-5 h-5" />
             <span>{error}</span>
          </div>
        )}

        {/* Gemini Analysis Section */}
        {cwaKey && !error && (
          <AIAnalyst 
            analysis={aiAnalysis} 
            loading={aiLoading} 
            onAnalyze={handleRunAnalysis} 
          />
        )}

        {/* Alerts Grid */}
        {cwaKey && (
          <div>
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                即時警報列表
                <span className="bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs">
                  {alerts.length}
                </span>
              </h2>
            </div>
            
            {loading && alerts.length === 0 ? (
               <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                  {[1,2,3].map(i => (
                    <div key={i} className="h-40 bg-gray-200 rounded-lg animate-pulse"></div>
                  ))}
               </div>
            ) : alerts.length > 0 ? (
              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {alerts.map((alert) => (
                  <AlertCard key={alert.id} alert={alert} />
                ))}
              </div>
            ) : (
              !loading && !error && (
                <div className="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-100">
                  <div className="inline-block p-4 bg-green-50 rounded-full mb-4">
                    <AlertCircle className="w-8 h-8 text-green-500" />
                  </div>
                  <h3 className="text-lg font-bold text-gray-800 mb-1">目前無特殊天氣警報</h3>
                  <p className="text-gray-500">台灣各地天氣狀況目前穩定。</p>
                </div>
              )
            )}
          </div>
        )}
      </main>

      <SettingsModal 
        apiKey={cwaKey} 
        onSave={handleSaveKey} 
        isOpen={isSettingsOpen} 
        onClose={() => setIsSettingsOpen(false)} 
      />
    </div>
  );
};

export default App;
