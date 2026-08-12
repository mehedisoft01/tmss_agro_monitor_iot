<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সার সুপারিশ কার্ড (Fertilizer Recommendation Card)</title>
</head>
<body onload="window.print()" style="font-family: Arial, sans-serif;  color: #000;">

    <div style="max-width: 850px; margin: 0 auto; background: #fff;  font-size: 11px;">

        <!-- Header Section -->
        <div style="text-align: center; margin-bottom: 5px;  padding-bottom: 5px;">
            <img src="{{ asset('images/govLogo.png') }}" alt="logo" style="height: 50px;">
            <div style="font-size: 14px; font-weight: bold;">গণপ্রজাতন্ত্রী বাংলাদেশ সরকার</div>
            <div style="font-size: 13px;">কৃষি মন্ত্রণালয়</div>
            <div style="font-size: 13px;">মৃত্তিকা সম্পদ উন্নয়ন ইনস্টিটিউট</div>
            <div style="font-size: 18px; font-weight: bold; margin-top: 5px;">সার সুপারিশ কার্ড</div>
        </div>

        <!-- Section A: Farmer Information -->
        <div style="font-weight: bold; margin-bottom: 5px;">ক. কৃষক, ভূমি ও মৃত্তিকা তথ্য</div>
        <div style="border: 1px solid #000; margin-bottom: 5px;">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; margin-left: 3px; font-size: 12px;">
                <tr>
                    <td style="width: 15%; padding: 3px 0;">কৃষকের নাম</td>
                    <td style="width: 35%;">: TMSS: মোঃ মিজানুর রহমান</td>
                    <td style="width: 20%;">নমুনা নম্বর</td>
                    <td style="width: 30%;">: BDKHJAKI - 2022 - 808</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">মোবাইল নম্বর</td>
                    <td>: </td>
                    <td>নমুনা সংগ্রহের তারিখ</td>
                    <td>: </td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">পিতা/স্বামীর নাম</td>
                    <td>: </td>
                    <td>ভূমির নাম</td>
                    <td>: </td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">গ্রামের নাম</td>
                    <td>: কান্দি</td>
                    <td>ভূমি শ্রেণী</td>
                    <td>: উঁচা/মাঝারি উঁচা/মাঝারি নিচু/নিচু/অতি নিচু</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">মৌজা ও দাগ নং</td>
                    <td>: </td>
                    <td>ভূমিরূপ</td>
                    <td>: </td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">উপজেলা</td>
                    <td>: </td>
                    <td>ফসলের নাম</td>
                    <td></td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">ইউনিয়ন</td>
                    <td>: </td>
                    <td>(১) রবি</td>
                    <td>: </td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">উপজেলা ও জেলা</td>
                    <td>: যশোহর, ঝিনাইদহ</td>
                    <td>(২) খরিফ-১</td>
                    <td>: </td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;"></td>
                    <td></td>
                    <td>(৩) খরিফ-২</td>
                    <td>: রোপা আমন</td>
                </tr>
            </table>
        </div>

        <!-- Section B: Test Results and Recommendation Table -->
        <div style="font-weight: bold; margin-bottom: 5px;">খ. মাটিতে পুষ্টি উপাদানের পরীক্ষণ ও সার সুপারিশ</div>

        <table style="width: 100%; border-collapse: collapse; text-align: center; border: 1px solid #000; font-size: 11px;">
           <thead>
                <tr style="background-color: #f2f2f2;">
                    <th colspan="2" style="border: 1px solid #000; padding: 4px;">
                        পুষ্টি উপাদান
                    </th>

                    <th colspan="2" style="border: 1px solid #000; padding: 4px;">
                        উর্বরতা শ্রেণী
                    </th>

                    <th rowspan="3" style="border: 1px solid #000; padding: 4px; width: 15%;">
                        সারের নাম
                    </th>

                    <th colspan="8" style="border: 1px solid #000; padding: 4px;">
                        সার সুপারিশ (গ্রাম/শতাংশ)
                    </th>
                </tr>

                <tr style="background-color: #f2f2f2;">
                    <th rowspan="2" style="border: 1px solid #000; padding: 4px; width: 20%;">
                        নাম ও একক
                    </th>

                    <th rowspan="2" style="border: 1px solid #000; padding: 4px; width: 6%;">
                        পরিমাণ
                    </th>

                    <th rowspan="2" style="border: 1px solid #000; padding: 4px; width: 8%;">
                        আগে আছে
                    </th>

                    <th rowspan="2" style="border: 1px solid #000; padding: 4px; width: 8%;">
                        ওয়েট আছে
                    </th>

                    <th colspan="8" style="border: 1px solid #000; padding: 4px;">
                        কোদাল
                    </th>
                </tr>

                <tr style="background-color: #f2f2f2;">
                    <th colspan="2" style="border: 1px solid #000; padding: 4px; width: 10%;">
                        সাংবাৎসরিক
                    </th>

                    <th colspan="3" style="border: 1px solid #000; padding: 4px; width: 15%;">
                        রবি
                    </th>

                    <th colspan="2" style="border: 1px solid #000; padding: 4px; width: 10%;">
                        খরিফ-১
                    </th>

                    <th style="border: 1px solid #000; padding: 4px;">
                        খরিফ-২
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">নাইট্রোজেন (N) (%)</td>
                    <td style="border: 1px solid #000; padding: 4px;">0.04</td>
                    <td style="border: 1px solid #000; padding: 4px;">অতি নিম্ন</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">ইউরিয়া</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">১০৩০</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">ফসফরাস (P) (মাইক্রোগ্রাম/গ্রাম মাটি)</td>
                    <td style="border: 1px solid #000; padding: 4px;">22.5</td>
                    <td style="border: 1px solid #000; padding: 4px;">অতি উচ্চ</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">টিএসপি/ডিএপি</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                     <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">৬৭৫</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">পটাশিয়াম (K) (মিলি গ্রাম/১০০ গ্রাম মাটি)</td>
                    <td style="border: 1px solid #000; padding: 4px;">0.18</td>
                    <td style="border: 1px solid #000; padding: 4px;">উচ্চ</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">এমওপি/এমএপি</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                     <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">৬৫০</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">গন্ধক (S) (মাইক্রোগ্রাম/গ্রাম মাটি)</td>
                    <td style="border: 1px solid #000; padding: 4px;">10.5</td>
                    <td style="border: 1px solid #000; padding: 4px;">নিম্ন</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">জিপসাম</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                     <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">৪৭০</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">দস্তা (Zn) (মাইক্রোগ্রাম/গ্রাম মাটি)</td>
                    <td style="border: 1px solid #000; padding: 4px;">2.90</td>
                    <td style="border: 1px solid #000; padding: 4px;">মাঝারি</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">জিঙ্ক সালফেট (মনো/হাইড্রেটেড)</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                     <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">৪০</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">বোরন (B) (মাইক্রোগ্রাম/গ্রাম মাটি)</td>
                    <td style="border: 1px solid #000; padding: 4px;">0.43</td>
                    <td style="border: 1px solid #000; padding: 4px;">মাঝারি</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">সোলাবোর/বোরিক এসিড</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                     <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">২৫</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">ক্যালসিয়াম (Ca) (মিলি গ্রাম/১০০ গ্রাম মাটি)</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">কৃষি চুন/ডলোমাইট</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                     <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">-</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">ম্যাগনেসিয়াম (Mg) (মিলি গ্রাম/১০০ গ্রাম মাটি)</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">ম্যাগনেসিয়াম সালফেট</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                     <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">-</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">মাটির অম্লতা (pH)</td>
                    <td style="border: 1px solid #000; padding: 4px;">7.5</td>
                    <td style="border: 1px solid #000; padding: 4px;">মৃদু ক্ষারীয়</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">কৃষি চুন/ডলোমাইট (কেজি/শতাংশ)</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                     <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">-</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px; text-align: left;">জৈব পদার্থ (OM) (%)</td>
                    <td style="border: 1px solid #000; padding: 4px;">1.8</td>
                    <td style="border: 1px solid #000; padding: 4px;">নিম্ন</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">গোবর (কেজি/শতাংশ)<br>কম্পোস্ট (কেজি/শতাংশ)</td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                     <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;"></td>
                    <td style="border: 1px solid #000; padding: 4px;">১২ কেজি</td>
                </tr>
            </tbody>
        </table>

        <!-- Footer Instructions and Signatures -->
        <div style="margin-top: 5px; font-size: 11px; line-height: 1.5;">
             কার্ডটি যত্নসহকারে সংরক্ষণ করুন। এটি মাটির শেণে পুষ্টি সম্পদ উন্নয়ন ইনস্টিটিউটের নিকটস্থ গবেষণাগার বা কার্যালয়ের সাথে যোগাযোগ করুন। মাটির প্রতিক্রিয়া (অমলমান) অনুসারে প্রতি শতাংশে ৪-৮ কেজি কৃষি চুন ব্যবহার করতে হবে।
        </div>

        <div style="margin-top: 10px; margin-bottom: 80px; text-align: right; font-size: 11px; line-height: 1.4;">
            <div style="font-weight: bold;">কর্মকর্তার স্বাক্ষর ও সীল</div>

        </div>

    </div>

</body>
</html>
