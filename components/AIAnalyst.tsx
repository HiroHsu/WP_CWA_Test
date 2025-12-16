import React from 'react';
import { Sparkles, ShieldCheck, AlertOctagon } from 'lucide-react';
import { AIAnalysis } from '../types';

interface AIAnalystProps {
  analysis: AIAnalysis | null;
  loading: boolean;
  onAnalyze: () => void;
}

export const AIAnalyst: React.FC<AIAnalystProps> = ({ analysis, loading, onAnalyze }) => {
  return (
    <div className="bg-gradient-to-br from-indigo-900 to-purple-900 rounded-xl shadow-xl overflow-hidden text-white mb-8">
      <div className="p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-indigo-500/20 rounded-lg backdrop-blur">
              <Sparkles className="w-6 h-6 text-yellow-300" />
            </div>
            <h2 className="text-xl font-bold text-white">Gemini 氣象智能分析</h2>
          </div>
          
          {!analysis && !loading && (
            <button 
              onClick={onAnalyze}
              className="px-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg text-sm font-medium transition-all flex items-center gap-2"
            >
              <Sparkles className="w-4 h-4" />
              立即分析
            </button>
          )}
        </div>

        {loading && (
          <div className="py-8 flex flex-col items-center justify-center space-y-3 animate-pulse">
            <div className="w-8 h-8 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            <span className="text-indigo-200 text-sm">Gemini 正在解讀氣象數據...</span>
          </div>
        )}

        {analysis && !loading && (
          <div className="animate-fade-in">
            <div className="mb-6">
              <h3 className="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-2">
                當前威脅等級
              </h3>
              <div className="flex items-center gap-2">
                {analysis.severityLevel === 'high' && <AlertOctagon className="w-5 h-5 text-red-400" />}
                {analysis.severityLevel === 'medium' && <AlertOctagon className="w-5 h-5 text-yellow-400" />}
                {analysis.severityLevel === 'low' && <ShieldCheck className="w-5 h-5 text-green-400" />}
                
                <span className={`text-lg font-bold ${
                  analysis.severityLevel === 'high' ? 'text-red-300' : 
                  analysis.severityLevel === 'medium' ? 'text-yellow-300' : 'text-green-300'
                }`}>
                  {analysis.severityLevel === 'high' ? '高風險' : analysis.severityLevel === 'medium' ? '中度風險' : '低風險'}
                </span>
              </div>
            </div>

            <div className="space-y-4">
              <div className="bg-white/5 rounded-lg p-4 backdrop-blur-sm border border-white/10">
                <p className="leading-relaxed text-gray-100">
                  {analysis.summary}
                </p>
              </div>

              <div>
                <h3 className="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-3">
                  安全建議
                </h3>
                <ul className="space-y-2">
                  {analysis.safetyTips.map((tip, idx) => (
                    <li key={idx} className="flex items-start gap-3 text-sm text-gray-200">
                      <ShieldCheck className="w-4 h-4 text-emerald-400 mt-0.5 shrink-0" />
                      <span>{tip}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
            
             <button 
              onClick={onAnalyze}
              className="mt-6 text-xs text-indigo-300 hover:text-white underline decoration-indigo-500/50 transition-colors"
            >
              重新分析
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
