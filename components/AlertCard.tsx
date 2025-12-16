import React from 'react';
import { WeatherAlert } from '../types';
import { AlertTriangle, CloudRain, Wind, Thermometer, Waves } from 'lucide-react';

interface AlertCardProps {
  alert: WeatherAlert;
}

const getIcon = (phenomena: string) => {
  if (phenomena.includes('雨')) return <CloudRain className="w-6 h-6 text-blue-500" />;
  if (phenomena.includes('風')) return <Wind className="w-6 h-6 text-gray-500" />;
  if (phenomena.includes('溫')) return <Thermometer className="w-6 h-6 text-red-500" />;
  if (phenomena.includes('浪')) return <Waves className="w-6 h-6 text-blue-700" />;
  return <AlertTriangle className="w-6 h-6 text-yellow-600" />;
};

const getColor = (significance: string) => {
  if (significance.includes('豪雨') || significance.includes('大豪雨') || significance.includes('超大豪雨')) return 'border-red-500 bg-red-50';
  if (significance.includes('大雨')) return 'border-yellow-500 bg-yellow-50';
  if (significance.includes('特報')) return 'border-orange-500 bg-orange-50';
  return 'border-blue-200 bg-white';
};

export const AlertCard: React.FC<AlertCardProps> = ({ alert }) => {
  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleString('zh-TW', {
      month: 'numeric',
      day: 'numeric',
      hour: 'numeric',
      minute: 'numeric'
    });
  };

  return (
    <div className={`border-l-4 rounded-r-lg shadow-sm p-4 mb-3 transition-all hover:shadow-md ${getColor(alert.phenomena + alert.significance)}`}>
      <div className="flex items-start justify-between">
        <div className="flex items-center gap-3">
          {getIcon(alert.phenomena)}
          <div>
            <h3 className="font-bold text-gray-800 text-lg">
              {alert.location}
            </h3>
            <div className="flex items-center gap-2 mt-1">
              <span className="px-2 py-0.5 rounded text-xs font-semibold bg-gray-800 text-white">
                {alert.phenomena}
              </span>
              <span className="text-sm font-medium text-gray-600">
                {alert.significance}
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <div className="mt-4 pt-3 border-t border-gray-200/50 text-xs text-gray-500 flex flex-col sm:flex-row sm:justify-between gap-1">
        <span>開始: {formatDate(alert.startTime)}</span>
        <span>結束: {formatDate(alert.endTime)}</span>
      </div>
    </div>
  );
};
